<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Services;

use Turkpin\AdminKit\Services\AuthService;
use Turkpin\AdminKit\Services\CacheService;
use Doctrine\ORM\EntityManagerInterface;

class AuditService
{
    private EntityManagerInterface $entityManager;
    private AuthService $authService;
    private CacheService $cacheService;
    private array $config;
    private array $trackedEntities;
    private bool $enabled;

    public function __construct(
        EntityManagerInterface $entityManager,
        AuthService $authService,
        CacheService $cacheService,
        array $config = []
    ) {
        $this->entityManager = $entityManager;
        $this->authService = $authService;
        $this->cacheService = $cacheService;
        $this->config = array_merge([
            'enabled' => true,
            'store_old_values' => true,
            'store_new_values' => true,
            'track_ip' => true,
            'track_user_agent' => true,
            'retention_days' => 90,
            'max_field_length' => 1000
        ], $config);
        
        $this->enabled = $this->config['enabled'];
        $this->trackedEntities = [
            'Turkpin\AdminKit\Entities\User' => [
                'fields' => ['name', 'email', 'isActive'],
                'events' => ['create', 'update', 'delete']
            ],
            'Turkpin\AdminKit\Entities\Role' => [
                'fields' => ['name', 'description'],
                'events' => ['create', 'update', 'delete']
            ],
            'Turkpin\AdminKit\Entities\Permission' => [
                'fields' => ['name', 'resource', 'action'],
                'events' => ['create', 'update', 'delete']
            ]
        ];
    }

    /**
     * Log entity creation
     */
    public function logCreate($entity): void
    {
        if (!$this->shouldTrack($entity, 'create')) {
            return;
        }

        $this->createAuditLog([
            'entity_type' => get_class($entity),
            'entity_id' => $this->getEntityId($entity),
            'event_type' => 'create',
            'old_values' => null,
            'new_values' => $this->config['store_new_values'] ? $this->extractEntityValues($entity) : null,
            'changed_fields' => null
        ]);
    }

    /**
     * Log entity update
     */
    public function logUpdate($entity, array $changeset = []): void
    {
        if (!$this->shouldTrack($entity, 'update')) {
            return;
        }

        $oldValues = [];
        $newValues = [];
        $changedFields = [];

        foreach ($changeset as $field => $values) {
            if ($this->shouldTrackField($entity, $field)) {
                $changedFields[] = $field;
                
                if ($this->config['store_old_values']) {
                    $oldValues[$field] = $this->sanitizeValue($values[0]);
                }
                
                if ($this->config['store_new_values']) {
                    $newValues[$field] = $this->sanitizeValue($values[1]);
                }
            }
        }

        if (!empty($changedFields)) {
            $this->createAuditLog([
                'entity_type' => get_class($entity),
                'entity_id' => $this->getEntityId($entity),
                'event_type' => 'update',
                'old_values' => $oldValues ?: null,
                'new_values' => $newValues ?: null,
                'changed_fields' => $changedFields
            ]);
        }
    }

    /**
     * Log entity deletion
     */
    public function logDelete($entity): void
    {
        if (!$this->shouldTrack($entity, 'delete')) {
            return;
        }

        $this->createAuditLog([
            'entity_type' => get_class($entity),
            'entity_id' => $this->getEntityId($entity),
            'event_type' => 'delete',
            'old_values' => $this->config['store_old_values'] ? $this->extractEntityValues($entity) : null,
            'new_values' => null,
            'changed_fields' => null
        ]);
    }

    /**
     * Log custom event
     */
    public function logEvent(string $eventType, string $description, array $context = []): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->createAuditLog([
            'entity_type' => 'system',
            'entity_id' => null,
            'event_type' => $eventType,
            'description' => $description,
            'old_values' => null,
            'new_values' => $context ?: null,
            'changed_fields' => null
        ]);
    }

    /**
     * Log login attempt
     */
    public function logLogin(string $email, bool $successful, string $reason = null): void
    {
        $this->logEvent('login', $successful ? 'Successful login' : 'Failed login attempt', [
            'email' => $email,
            'successful' => $successful,
            'reason' => $reason,
            'ip_address' => $this->getClientIp(),
            'user_agent' => $this->getUserAgent()
        ]);
    }

    /**
     * Log logout
     */
    public function logLogout(): void
    {
        $user = $this->authService->getCurrentUser();
        $this->logEvent('logout', 'User logged out', [
            'user_id' => $user ? $user->getId() : null,
            'user_email' => $user ? $user->getEmail() : null
        ]);
    }

    /**
     * Log permission change
     */
    public function logPermissionChange(string $action, array $context): void
    {
        $this->logEvent('permission_change', "Permission {$action}", $context);
    }

    /**
     * Get audit logs for entity
     */
    public function getEntityAuditLogs($entity, int $limit = 50): array
    {
        $cacheKey = "audit_logs:" . get_class($entity) . ":" . $this->getEntityId($entity);
        
        return $this->cacheService->remember($cacheKey, function() use ($entity, $limit) {
            return $this->queryAuditLogs([
                'entity_type' => get_class($entity),
                'entity_id' => $this->getEntityId($entity)
            ], $limit);
        }, 300);
    }

    /**
     * Get audit logs with filters
     */
    public function getAuditLogs(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        return $this->queryAuditLogs($filters, $limit, $offset);
    }

    /**
     * Get audit statistics
     */
    public function getAuditStats(): array
    {
        return $this->cacheService->remember('audit_stats', function() {
            // In a real implementation, these would be database queries
            return [
                'total_logs' => 0,
                'logs_today' => 0,
                'logs_this_week' => 0,
                'logs_this_month' => 0,
                'most_active_users' => [],
                'most_common_events' => [],
                'login_attempts_today' => 0,
                'failed_logins_today' => 0
            ];
        }, 600);
    }

    /**
     * Clean old audit logs
     */
    public function cleanOldLogs(): int
    {
        if ($this->config['retention_days'] <= 0) {
            return 0;
        }

        $cutoffDate = new \DateTime();
        $cutoffDate->modify("-{$this->config['retention_days']} days");

        // In a real implementation, this would delete from database
        // For now, just clear old cache entries
        $this->cacheService->clearPattern('audit_logs:*');
        
        return 0; // Return number of deleted logs
    }

    /**
     * Register entity for tracking
     */
    public function registerEntity(string $entityClass, array $config): void
    {
        $this->trackedEntities[$entityClass] = array_merge([
            'fields' => [],
            'events' => ['create', 'update', 'delete']
        ], $config);
    }

    /**
     * Enable/disable audit logging
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    // Private helper methods

    private function shouldTrack($entity, string $eventType): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $entityClass = get_class($entity);
        
        if (!isset($this->trackedEntities[$entityClass])) {
            return false;
        }

        $config = $this->trackedEntities[$entityClass];
        return in_array($eventType, $config['events']);
    }

    private function shouldTrackField($entity, string $field): bool
    {
        $entityClass = get_class($entity);
        
        if (!isset($this->trackedEntities[$entityClass])) {
            return false;
        }

        $config = $this->trackedEntities[$entityClass];
        
        // If no specific fields configured, track all
        if (empty($config['fields'])) {
            return true;
        }

        return in_array($field, $config['fields']);
    }

    private function createAuditLog(array $data): void
    {
        $user = $this->authService->getCurrentUser();
        
        $auditData = array_merge($data, [
            'user_id' => $user ? $user->getId() : null,
            'user_email' => $user ? $user->getEmail() : null,
            'ip_address' => $this->config['track_ip'] ? $this->getClientIp() : null,
            'user_agent' => $this->config['track_user_agent'] ? $this->getUserAgent() : null,
            'created_at' => new \DateTime(),
            'session_id' => session_id() ?: null
        ]);

        // In a real implementation, this would save to database
        // For now, store in cache for demonstration
        $logId = uniqid('audit_', true);
        $cacheKey = "audit_log:{$logId}";
        $this->cacheService->set($cacheKey, $auditData, 86400 * $this->config['retention_days']);

        // Also store in a list for querying
        $listKey = "audit_logs:all";
        $existingLogs = $this->cacheService->get($listKey, fn() => []);
        $existingLogs[] = $logId;
        
        // Keep only recent logs in cache
        if (count($existingLogs) > 1000) {
            $existingLogs = array_slice($existingLogs, -1000);
        }
        
        $this->cacheService->set($listKey, $existingLogs, 86400);

        // Log to PHP error log for debugging
        error_log("AdminKit Audit: " . json_encode($auditData));
    }

    private function queryAuditLogs(array $filters, int $limit, int $offset = 0): array
    {
        // In a real implementation, this would be a database query
        // For now, return from cache
        $listKey = "audit_logs:all";
        $logIds = $this->cacheService->get($listKey, fn() => []);
        
        $logs = [];
        $processed = 0;
        $returned = 0;
        
        foreach (array_reverse($logIds) as $logId) {
            if ($processed < $offset) {
                $processed++;
                continue;
            }
            
            if ($returned >= $limit) {
                break;
            }
            
            $log = $this->cacheService->get("audit_log:{$logId}");
            if ($log && $this->matchesFilters($log, $filters)) {
                $logs[] = $log;
                $returned++;
            }
            
            $processed++;
        }
        
        return $logs;
    }

    private function matchesFilters(array $log, array $filters): bool
    {
        foreach ($filters as $key => $value) {
            if (!isset($log[$key]) || $log[$key] !== $value) {
                return false;
            }
        }
        
        return true;
    }

    private function getEntityId($entity): ?int
    {
        if (method_exists($entity, 'getId')) {
            return $entity->getId();
        }
        
        return null;
    }

    private function extractEntityValues($entity): array
    {
        $values = [];
        $entityClass = get_class($entity);
        
        if (!isset($this->trackedEntities[$entityClass])) {
            return $values;
        }

        $config = $this->trackedEntities[$entityClass];
        $fields = $config['fields'];
        
        // If no specific fields, extract common ones
        if (empty($fields)) {
            $fields = ['name', 'email', 'title', 'description'];
        }

        foreach ($fields as $field) {
            $getter = 'get' . ucfirst($field);
            if (method_exists($entity, $getter)) {
                $value = $entity->$getter();
                $values[$field] = $this->sanitizeValue($value);
            }
        }

        return $values;
    }

    private function sanitizeValue($value): mixed
    {
        if ($value instanceof \DateTime) {
            return $value->format('Y-m-d H:i:s');
        }
        
        if (is_object($value)) {
            if (method_exists($value, 'getId')) {
                return $value->getId();
            }
            return get_class($value);
        }
        
        if (is_string($value) && strlen($value) > $this->config['max_field_length']) {
            return substr($value, 0, $this->config['max_field_length']) . '...';
        }
        
        return $value;
    }

    private function getClientIp(): ?string
    {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                return $ip;
            }
        }
        
        return null;
    }

    private function getUserAgent(): ?string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? null;
    }

    /**
     * Get audit trail for display
     */
    public function getAuditTrail($entity): array
    {
        $logs = $this->getEntityAuditLogs($entity);
        
        return array_map(function($log) {
            return [
                'event_type' => $log['event_type'],
                'description' => $this->formatLogDescription($log),
                'user' => $log['user_email'] ?? 'System',
                'created_at' => $log['created_at'],
                'ip_address' => $log['ip_address'],
                'changes' => $this->formatChanges($log)
            ];
        }, $logs);
    }

    private function formatLogDescription(array $log): string
    {
        $descriptions = [
            'create' => 'Created',
            'update' => 'Updated',
            'delete' => 'Deleted',
            'login' => 'Logged in',
            'logout' => 'Logged out'
        ];

        $base = $descriptions[$log['event_type']] ?? ucfirst($log['event_type']);
        
        if (!empty($log['changed_fields'])) {
            $base .= ' (' . implode(', ', $log['changed_fields']) . ')';
        }
        
        return $base;
    }

    private function formatChanges(array $log): array
    {
        $changes = [];
        
        if (!empty($log['changed_fields']) && !empty($log['old_values']) && !empty($log['new_values'])) {
            foreach ($log['changed_fields'] as $field) {
                $oldValue = $log['old_values'][$field] ?? null;
                $newValue = $log['new_values'][$field] ?? null;
                
                $changes[] = [
                    'field' => $field,
                    'old_value' => $oldValue,
                    'new_value' => $newValue
                ];
            }
        }
        
        return $changes;
    }
}
