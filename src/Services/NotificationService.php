<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Services;

use Turkpin\AdminKit\Services\CacheService;
use Turkpin\AdminKit\Services\AuthService;

class NotificationService
{
    private CacheService $cacheService;
    private AuthService $authService;
    private array $config;
    private array $channels;

    public function __construct(
        CacheService $cacheService,
        AuthService $authService,
        array $config = []
    ) {
        $this->cacheService = $cacheService;
        $this->authService = $authService;
        $this->config = array_merge([
            'default_channel' => 'toast',
            'retention_days' => 30,
            'max_notifications' => 100,
            'realtime_enabled' => true,
            'email_enabled' => true,
            'websocket_enabled' => false
        ], $config);
        
        $this->registerChannels();
    }

    /**
     * Register notification channels
     */
    private function registerChannels(): void
    {
        $this->channels = [
            'toast' => [
                'name' => 'Toast Notifications',
                'description' => 'Pop-up notifications in the UI',
                'realtime' => true,
                'persistent' => false
            ],
            'flash' => [
                'name' => 'Flash Messages',
                'description' => 'Page refresh notifications',
                'realtime' => false,
                'persistent' => true
            ],
            'alert' => [
                'name' => 'Alert Dialogs',
                'description' => 'Modal alert notifications',
                'realtime' => true,
                'persistent' => false
            ],
            'email' => [
                'name' => 'Email Notifications',
                'description' => 'Email delivery',
                'realtime' => false,
                'persistent' => true
            ],
            'database' => [
                'name' => 'Database Storage',
                'description' => 'Persistent notification storage',
                'realtime' => false,
                'persistent' => true
            ]
        ];
    }

    /**
     * Send notification
     */
    public function send(array $notification): bool
    {
        $notification = $this->normalizeNotification($notification);
        
        if (!$this->validateNotification($notification)) {
            return false;
        }

        $success = true;
        $channels = $notification['channels'] ?? [$this->config['default_channel']];

        foreach ($channels as $channel) {
            if (!$this->sendToChannel($notification, $channel)) {
                $success = false;
            }
        }

        // Store in database channel for persistence
        if (in_array('database', $channels) || $notification['persistent'] ?? false) {
            $this->storeNotification($notification);
        }

        return $success;
    }

    /**
     * Send to specific channel
     */
    private function sendToChannel(array $notification, string $channel): bool
    {
        switch ($channel) {
            case 'toast':
                return $this->sendToast($notification);
            case 'flash':
                return $this->sendFlash($notification);
            case 'alert':
                return $this->sendAlert($notification);
            case 'email':
                return $this->sendEmail($notification);
            case 'database':
                return $this->storeNotification($notification);
            default:
                return false;
        }
    }

    /**
     * Send toast notification
     */
    private function sendToast(array $notification): bool
    {
        $userId = $notification['user_id'];
        $toastKey = "notifications:toast:{$userId}";
        
        $toasts = $this->cacheService->get($toastKey, fn() => []);
        $toasts[] = [
            'id' => uniqid('toast_'),
            'type' => $notification['type'],
            'title' => $notification['title'],
            'message' => $notification['message'],
            'timestamp' => time(),
            'auto_dismiss' => $notification['auto_dismiss'] ?? true,
            'duration' => $notification['duration'] ?? 5000
        ];

        // Keep only recent toasts
        $toasts = array_slice($toasts, -20);
        
        return $this->cacheService->set($toastKey, $toasts, 3600);
    }

    /**
     * Send flash message
     */
    private function sendFlash(array $notification): bool
    {
        $userId = $notification['user_id'];
        $flashKey = "notifications:flash:{$userId}";
        
        $flashes = $this->cacheService->get($flashKey, fn() => []);
        $flashes[] = [
            'type' => $notification['type'],
            'message' => $notification['message'],
            'dismissible' => $notification['dismissible'] ?? true
        ];

        return $this->cacheService->set($flashKey, $flashes, 1800); // 30 minutes
    }

    /**
     * Send alert notification
     */
    private function sendAlert(array $notification): bool
    {
        $userId = $notification['user_id'];
        $alertKey = "notifications:alert:{$userId}";
        
        $alert = [
            'id' => uniqid('alert_'),
            'type' => $notification['type'],
            'title' => $notification['title'],
            'message' => $notification['message'],
            'actions' => $notification['actions'] ?? [],
            'timestamp' => time()
        ];

        return $this->cacheService->set($alertKey, $alert, 300); // 5 minutes
    }

    /**
     * Send email notification
     */
    private function sendEmail(array $notification): bool
    {
        if (!$this->config['email_enabled']) {
            return false;
        }

        // In real implementation, integrate with email service
        error_log("Email notification: " . json_encode($notification));
        
        return true;
    }

    /**
     * Store notification in database
     */
    private function storeNotification(array $notification): bool
    {
        $notificationId = uniqid('notif_');
        $key = "notifications:stored:{$notification['user_id']}:{$notificationId}";
        
        $stored = [
            'id' => $notificationId,
            'user_id' => $notification['user_id'],
            'type' => $notification['type'],
            'title' => $notification['title'],
            'message' => $notification['message'],
            'data' => $notification['data'] ?? [],
            'read' => false,
            'created_at' => time(),
            'expires_at' => time() + (86400 * $this->config['retention_days'])
        ];

        // Store individual notification
        $this->cacheService->set($key, $stored, 86400 * $this->config['retention_days']);

        // Add to user's notification list
        $listKey = "notifications:list:{$notification['user_id']}";
        $notifications = $this->cacheService->get($listKey, fn() => []);
        $notifications[] = $notificationId;
        
        // Keep only recent notifications
        if (count($notifications) > $this->config['max_notifications']) {
            $notifications = array_slice($notifications, -$this->config['max_notifications']);
        }
        
        $this->cacheService->set($listKey, $notifications, 86400 * $this->config['retention_days']);

        return true;
    }

    /**
     * Get notifications for user
     */
    public function getNotifications(int $userId, array $options = []): array
    {
        $type = $options['type'] ?? 'all';
        $limit = min(100, $options['limit'] ?? 20);
        $unreadOnly = $options['unread_only'] ?? false;

        switch ($type) {
            case 'toast':
                return $this->getToastNotifications($userId);
            case 'flash':
                return $this->getFlashNotifications($userId);
            case 'alert':
                return $this->getAlertNotifications($userId);
            case 'stored':
            case 'database':
                return $this->getStoredNotifications($userId, $limit, $unreadOnly);
            default:
                return [
                    'toast' => $this->getToastNotifications($userId),
                    'flash' => $this->getFlashNotifications($userId),
                    'alert' => $this->getAlertNotifications($userId),
                    'stored' => $this->getStoredNotifications($userId, $limit, $unreadOnly)
                ];
        }
    }

    /**
     * Get toast notifications
     */
    private function getToastNotifications(int $userId): array
    {
        $toasts = $this->cacheService->get("notifications:toast:{$userId}", fn() => []);
        
        // Filter out expired toasts
        $now = time();
        return array_filter($toasts, fn($toast) => 
            ($now - $toast['timestamp']) < 3600 // 1 hour max
        );
    }

    /**
     * Get flash notifications
     */
    private function getFlashNotifications(int $userId): array
    {
        $flashes = $this->cacheService->get("notifications:flash:{$userId}", fn() => []);
        
        // Clear flashes after retrieval (they're meant to be shown once)
        $this->cacheService->delete("notifications:flash:{$userId}");
        
        return $flashes;
    }

    /**
     * Get alert notifications
     */
    private function getAlertNotifications(int $userId): array
    {
        $alert = $this->cacheService->get("notifications:alert:{$userId}");
        
        if ($alert) {
            // Clear alert after retrieval
            $this->cacheService->delete("notifications:alert:{$userId}");
            return [$alert];
        }
        
        return [];
    }

    /**
     * Get stored notifications
     */
    private function getStoredNotifications(int $userId, int $limit, bool $unreadOnly): array
    {
        $listKey = "notifications:list:{$userId}";
        $notificationIds = $this->cacheService->get($listKey, fn() => []);
        
        $notifications = [];
        $count = 0;
        
        // Get notifications in reverse order (newest first)
        foreach (array_reverse($notificationIds) as $notificationId) {
            if ($count >= $limit) break;
            
            $key = "notifications:stored:{$userId}:{$notificationId}";
            $notification = $this->cacheService->get($key);
            
            if ($notification) {
                // Check if expired
                if ($notification['expires_at'] < time()) {
                    continue;
                }
                
                // Filter unread if requested
                if ($unreadOnly && $notification['read']) {
                    continue;
                }
                
                $notifications[] = $notification;
                $count++;
            }
        }
        
        return $notifications;
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(int $userId, string $notificationId): bool
    {
        $key = "notifications:stored:{$userId}:{$notificationId}";
        $notification = $this->cacheService->get($key);
        
        if (!$notification) {
            return false;
        }
        
        $notification['read'] = true;
        $notification['read_at'] = time();
        
        return $this->cacheService->set($key, $notification, 
            $notification['expires_at'] - time());
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(int $userId): bool
    {
        $listKey = "notifications:list:{$userId}";
        $notificationIds = $this->cacheService->get($listKey, fn() => []);
        
        $success = true;
        foreach ($notificationIds as $notificationId) {
            if (!$this->markAsRead($userId, $notificationId)) {
                $success = false;
            }
        }
        
        return $success;
    }

    /**
     * Delete notification
     */
    public function deleteNotification(int $userId, string $notificationId): bool
    {
        $key = "notifications:stored:{$userId}:{$notificationId}";
        $this->cacheService->delete($key);
        
        // Remove from user's list
        $listKey = "notifications:list:{$userId}";
        $notifications = $this->cacheService->get($listKey, fn() => []);
        $notifications = array_filter($notifications, fn($id) => $id !== $notificationId);
        $this->cacheService->set($listKey, $notifications, 86400 * $this->config['retention_days']);
        
        return true;
    }

    /**
     * Get notification statistics
     */
    public function getStats(int $userId): array
    {
        $stored = $this->getStoredNotifications($userId, 100, false);
        $unread = array_filter($stored, fn($n) => !$n['read']);
        
        $typeStats = [];
        foreach ($stored as $notification) {
            $type = $notification['type'];
            $typeStats[$type] = ($typeStats[$type] ?? 0) + 1;
        }
        
        return [
            'total' => count($stored),
            'unread' => count($unread),
            'read' => count($stored) - count($unread),
            'by_type' => $typeStats,
            'has_toast' => !empty($this->getToastNotifications($userId)),
            'has_alert' => !empty($this->getAlertNotifications($userId))
        ];
    }

    /**
     * Clean expired notifications
     */
    public function cleanExpiredNotifications(): int
    {
        // In real implementation, this would clean database records
        // For now, we rely on cache TTL
        return 0;
    }

    /**
     * Helper methods for common notification types
     */
    public function success(int $userId, string $message, array $options = []): bool
    {
        return $this->send(array_merge([
            'user_id' => $userId,
            'type' => 'success',
            'title' => 'Success',
            'message' => $message
        ], $options));
    }

    public function error(int $userId, string $message, array $options = []): bool
    {
        return $this->send(array_merge([
            'user_id' => $userId,
            'type' => 'error',
            'title' => 'Error',
            'message' => $message,
            'persistent' => true
        ], $options));
    }

    public function warning(int $userId, string $message, array $options = []): bool
    {
        return $this->send(array_merge([
            'user_id' => $userId,
            'type' => 'warning',
            'title' => 'Warning',
            'message' => $message
        ], $options));
    }

    public function info(int $userId, string $message, array $options = []): bool
    {
        return $this->send(array_merge([
            'user_id' => $userId,
            'type' => 'info',
            'title' => 'Information',
            'message' => $message
        ], $options));
    }

    /**
     * Normalize notification data
     */
    private function normalizeNotification(array $notification): array
    {
        return array_merge([
            'type' => 'info',
            'title' => 'Notification',
            'message' => '',
            'channels' => [$this->config['default_channel']],
            'persistent' => false,
            'auto_dismiss' => true,
            'dismissible' => true,
            'data' => []
        ], $notification);
    }

    /**
     * Validate notification data
     */
    private function validateNotification(array $notification): bool
    {
        return !empty($notification['user_id']) && 
               !empty($notification['message']) &&
               in_array($notification['type'], ['success', 'error', 'warning', 'info']);
    }

    /**
     * Render notification UI components
     */
    public function renderNotificationCenter(int $userId): string
    {
        $stats = $this->getStats($userId);
        
        return '
        <div class="notification-center relative">
            <button onclick="toggleNotificationCenter()" 
                    class="relative p-2 text-gray-600 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM12 3a9 9 0 11-9 9 9 9 0 019-9z"></path>
                </svg>
                ' . ($stats['unread'] > 0 ? '<span class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full">' . $stats['unread'] . '</span>' : '') . '
            </button>
            
            <div id="notification-dropdown" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
                <div class="p-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900">Notifications</h3>
                        <div class="flex space-x-2">
                            <button onclick="markAllAsRead()" class="text-sm text-indigo-600 hover:text-indigo-800">Mark all read</button>
                            <button onclick="refreshNotifications()" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <p class="text-sm text-gray-500">' . $stats['unread'] . ' unread of ' . $stats['total'] . ' total</p>
                </div>
                
                <div id="notification-list" class="max-h-96 overflow-y-auto">
                    <!-- Notifications will be loaded here -->
                </div>
                
                <div class="p-3 border-t border-gray-200 text-center">
                    <a href="/admin/notifications" class="text-sm text-indigo-600 hover:text-indigo-800">View all notifications</a>
                </div>
            </div>
        </div>';
    }

    /**
     * Render toast container
     */
    public function renderToastContainer(): string
    {
        return '
        <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>
        
        <script>
        class NotificationManager {
            constructor() {
                this.toastContainer = document.getElementById("toast-container");
                this.checkForToasts();
                this.setupEventListeners();
            }
            
            checkForToasts() {
                fetch("/admin/notifications/toast")
                    .then(response => response.json())
                    .then(toasts => {
                        toasts.forEach(toast => this.showToast(toast));
                    })
                    .catch(console.error);
            }
            
            showToast(toast) {
                const toastEl = document.createElement("div");
                toastEl.className = `toast-notification bg-white border-l-4 border-${this.getColorClass(toast.type)} rounded-lg shadow-lg p-4 max-w-sm transform transition-all duration-300 translate-x-full`;
                toastEl.innerHTML = `
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            ${this.getIcon(toast.type)}
                        </div>
                        <div class="ml-3 flex-1">
                            <p class="text-sm font-medium text-gray-900">${toast.title}</p>
                            <p class="text-sm text-gray-600">${toast.message}</p>
                        </div>
                        <button onclick="this.parentElement.parentElement.remove()" class="flex-shrink-0 ml-4 text-gray-400 hover:text-gray-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                `;
                
                this.toastContainer.appendChild(toastEl);
                
                // Animate in
                setTimeout(() => {
                    toastEl.classList.remove("translate-x-full");
                }, 100);
                
                // Auto dismiss
                if (toast.auto_dismiss) {
                    setTimeout(() => {
                        this.dismissToast(toastEl);
                    }, toast.duration || 5000);
                }
            }
            
            dismissToast(toastEl) {
                toastEl.classList.add("translate-x-full");
                setTimeout(() => toastEl.remove(), 300);
            }
            
            getColorClass(type) {
                const colors = {
                    success: "green-500",
                    error: "red-500", 
                    warning: "yellow-500",
                    info: "blue-500"
                };
                return colors[type] || "gray-500";
            }
            
            getIcon(type) {
                const icons = {
                    success: `<svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>`,
                    error: `<svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>`,
                    warning: `<svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path></svg>`,
                    info: `<svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`
                };
                return icons[type] || icons.info;
            }
            
            setupEventListeners() {
                // Real-time notifications via polling (in production, use WebSockets)
                if (' . ($this->config['realtime_enabled'] ? 'true' : 'false') . ') {
                    setInterval(() => this.checkForToasts(), 30000); // Check every 30 seconds
                }
            }
        }
        
        // Initialize notification manager
        document.addEventListener("DOMContentLoaded", () => {
            window.notificationManager = new NotificationManager();
        });
        
        function toggleNotificationCenter() {
            const dropdown = document.getElementById("notification-dropdown");
            dropdown.classList.toggle("hidden");
            
            if (!dropdown.classList.contains("hidden")) {
                loadNotifications();
            }
        }
        
        function loadNotifications() {
            fetch("/admin/notifications/stored")
                .then(response => response.json())
                .then(notifications => {
                    const list = document.getElementById("notification-list");
                    list.innerHTML = notifications.map(n => `
                        <div class="p-3 border-b border-gray-100 ${n.read ? "bg-gray-50" : "bg-white"} hover:bg-gray-50">
                            <div class="flex items-start">
                                <div class="flex-shrink-0 mt-1">
                                    ${window.notificationManager.getIcon(n.type)}
                                </div>
                                <div class="ml-3 flex-1">
                                    <p class="text-sm font-medium text-gray-900">${n.title}</p>
                                    <p class="text-sm text-gray-600">${n.message}</p>
                                    <p class="text-xs text-gray-400 mt-1">${new Date(n.created_at * 1000).toLocaleString()}</p>
                                </div>
                                ${!n.read ? `<button onclick="markAsRead('${n.id}')" class="text-xs text-indigo-600">Mark read</button>` : ""}
                            </div>
                        </div>
                    `).join("");
                })
                .catch(console.error);
        }
        
        function markAsRead(notificationId) {
            fetch(`/admin/notifications/${notificationId}/read`, { method: "POST" })
                .then(() => loadNotifications())
                .catch(console.error);
        }
        
        function markAllAsRead() {
            fetch("/admin/notifications/mark-all-read", { method: "POST" })
                .then(() => loadNotifications())
                .catch(console.error);
        }
        
        function refreshNotifications() {
            loadNotifications();
            window.notificationManager.checkForToasts();
        }
        </script>';
    }
}
