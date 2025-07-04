<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Turkpin\AdminKit\Services\AuthService;
use Turkpin\AdminKit\Services\CacheService;
use Turkpin\AdminKit\Services\SearchService;
use Turkpin\AdminKit\Services\ExportService;
use Turkpin\AdminKit\Services\BatchOperationService;
use Doctrine\ORM\EntityManagerInterface;

class ApiController
{
    private AuthService $authService;
    private EntityManagerInterface $entityManager;
    private CacheService $cacheService;
    private SearchService $searchService;
    private ExportService $exportService;
    private BatchOperationService $batchService;
    private array $config;
    private array $rateLimits;

    public function __construct(
        AuthService $authService,
        EntityManagerInterface $entityManager,
        CacheService $cacheService,
        SearchService $searchService,
        ExportService $exportService,
        BatchOperationService $batchService,
        array $config = []
    ) {
        $this->authService = $authService;
        $this->entityManager = $entityManager;
        $this->cacheService = $cacheService;
        $this->searchService = $searchService;
        $this->exportService = $exportService;
        $this->batchService = $batchService;
        $this->config = $config;
        $this->rateLimits = [
            'default' => 100, // requests per hour
            'search' => 500,
            'export' => 10,
            'batch' => 50
        ];
    }

    /**
     * Get entity list with pagination
     */
    public function index(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $entityName = $args['entity'] ?? null;
        
        if (!$this->checkRateLimit($request, 'default') || 
            !$this->checkEntityAccess($entityName, 'index')) {
            return $this->errorResponse($response, 'Access denied', 403);
        }

        try {
            $queryParams = $request->getQueryParams();
            $page = max(1, (int)($queryParams['page'] ?? 1));
            $limit = min(100, max(1, (int)($queryParams['limit'] ?? 20)));
            $search = trim($queryParams['search'] ?? '');
            $sort = $queryParams['sort'] ?? 'id';
            $direction = strtoupper($queryParams['direction'] ?? 'DESC');

            $entityClass = $this->getEntityClass($entityName);
            if (!$entityClass) {
                return $this->errorResponse($response, 'Entity not found', 404);
            }

            $repository = $this->entityManager->getRepository($entityClass);
            $queryBuilder = $repository->createQueryBuilder('e');

            // Search
            if ($search) {
                $searchFields = $this->getSearchFields($entityName);
                $searchConditions = [];
                foreach ($searchFields as $field) {
                    $searchConditions[] = "e.{$field} LIKE :search";
                }
                if ($searchConditions) {
                    $queryBuilder->where(implode(' OR ', $searchConditions))
                               ->setParameter('search', "%{$search}%");
                }
            }

            // Count total
            $totalQuery = clone $queryBuilder;
            $totalQuery->select('COUNT(e.id)');
            $total = $totalQuery->getQuery()->getSingleScalarResult();

            // Apply pagination and sorting
            $offset = ($page - 1) * $limit;
            $queryBuilder->orderBy("e.{$sort}", $direction)
                        ->setFirstResult($offset)
                        ->setMaxResults($limit);

            $entities = $queryBuilder->getQuery()->getResult();

            $data = [
                'data' => array_map([$this, 'serializeEntity'], $entities),
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'total_pages' => ceil($total / $limit),
                    'has_more' => $page < ceil($total / $limit)
                ],
                'meta' => [
                    'entity' => $entityName,
                    'search' => $search,
                    'sort' => $sort,
                    'direction' => $direction
                ]
            ];

            return $this->jsonResponse($response, $data);

        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Server error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get single entity
     */
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $entityName = $args['entity'] ?? null;
        $id = (int)($args['id'] ?? 0);

        if (!$this->checkRateLimit($request, 'default') || 
            !$this->checkEntityAccess($entityName, 'show')) {
            return $this->errorResponse($response, 'Access denied', 403);
        }

        try {
            $entityClass = $this->getEntityClass($entityName);
            if (!$entityClass) {
                return $this->errorResponse($response, 'Entity not found', 404);
            }

            $entity = $this->entityManager->getRepository($entityClass)->find($id);
            if (!$entity) {
                return $this->errorResponse($response, 'Record not found', 404);
            }

            $data = [
                'data' => $this->serializeEntity($entity),
                'meta' => [
                    'entity' => $entityName,
                    'id' => $id
                ]
            ];

            return $this->jsonResponse($response, $data);

        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Server error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Create new entity
     */
    public function create(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $entityName = $args['entity'] ?? null;

        if (!$this->checkRateLimit($request, 'default') || 
            !$this->checkEntityAccess($entityName, 'create')) {
            return $this->errorResponse($response, 'Access denied', 403);
        }

        try {
            $data = $this->getRequestData($request);
            $entityClass = $this->getEntityClass($entityName);
            
            if (!$entityClass) {
                return $this->errorResponse($response, 'Entity not found', 404);
            }

            $entity = new $entityClass();
            $this->hydrateEntity($entity, $data);
            
            $this->entityManager->persist($entity);
            $this->entityManager->flush();

            // Clear cache
            $this->cacheService->invalidateEntity($entityName);

            $responseData = [
                'data' => $this->serializeEntity($entity),
                'message' => 'Entity created successfully',
                'meta' => [
                    'entity' => $entityName,
                    'action' => 'create'
                ]
            ];

            return $this->jsonResponse($response, $responseData, 201);

        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Creation failed: ' . $e->getMessage(), 400);
        }
    }

    /**
     * Update entity
     */
    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $entityName = $args['entity'] ?? null;
        $id = (int)($args['id'] ?? 0);

        if (!$this->checkRateLimit($request, 'default') || 
            !$this->checkEntityAccess($entityName, 'update')) {
            return $this->errorResponse($response, 'Access denied', 403);
        }

        try {
            $data = $this->getRequestData($request);
            $entityClass = $this->getEntityClass($entityName);
            
            if (!$entityClass) {
                return $this->errorResponse($response, 'Entity not found', 404);
            }

            $entity = $this->entityManager->getRepository($entityClass)->find($id);
            if (!$entity) {
                return $this->errorResponse($response, 'Record not found', 404);
            }

            $this->hydrateEntity($entity, $data);
            $this->entityManager->flush();

            // Clear cache
            $this->cacheService->invalidateEntity($entityName, $id);

            $responseData = [
                'data' => $this->serializeEntity($entity),
                'message' => 'Entity updated successfully',
                'meta' => [
                    'entity' => $entityName,
                    'id' => $id,
                    'action' => 'update'
                ]
            ];

            return $this->jsonResponse($response, $responseData);

        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Update failed: ' . $e->getMessage(), 400);
        }
    }

    /**
     * Delete entity
     */
    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $entityName = $args['entity'] ?? null;
        $id = (int)($args['id'] ?? 0);

        if (!$this->checkRateLimit($request, 'default') || 
            !$this->checkEntityAccess($entityName, 'delete')) {
            return $this->errorResponse($response, 'Access denied', 403);
        }

        try {
            $entityClass = $this->getEntityClass($entityName);
            
            if (!$entityClass) {
                return $this->errorResponse($response, 'Entity not found', 404);
            }

            $entity = $this->entityManager->getRepository($entityClass)->find($id);
            if (!$entity) {
                return $this->errorResponse($response, 'Record not found', 404);
            }

            $this->entityManager->remove($entity);
            $this->entityManager->flush();

            // Clear cache
            $this->cacheService->invalidateEntity($entityName, $id);

            $responseData = [
                'message' => 'Entity deleted successfully',
                'meta' => [
                    'entity' => $entityName,
                    'id' => $id,
                    'action' => 'delete'
                ]
            ];

            return $this->jsonResponse($response, $responseData);

        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Deletion failed: ' . $e->getMessage(), 400);
        }
    }

    /**
     * Global search API
     */
    public function search(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (!$this->checkRateLimit($request, 'search')) {
            return $this->errorResponse($response, 'Rate limit exceeded', 429);
        }

        try {
            $queryParams = $request->getQueryParams();
            $query = trim($queryParams['q'] ?? '');
            $limit = min(100, max(1, (int)($queryParams['limit'] ?? 50)));

            if (strlen($query) < 2) {
                return $this->errorResponse($response, 'Query too short', 400);
            }

            $results = $this->searchService->globalSearch($query, $limit);

            return $this->jsonResponse($response, $results);

        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Search failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Autocomplete for associations
     */
    public function autocomplete(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (!$this->checkRateLimit($request, 'search')) {
            return $this->errorResponse($response, 'Rate limit exceeded', 429);
        }

        try {
            $data = $this->getRequestData($request);
            $entityName = $data['entity'] ?? '';
            $query = trim($data['query'] ?? '');
            $limit = min(50, max(1, (int)($data['limit'] ?? 20)));

            if (!$this->checkEntityAccess($entityName, 'index')) {
                return $this->errorResponse($response, 'Access denied', 403);
            }

            $entityClass = $this->getEntityClass($entityName);
            if (!$entityClass) {
                return $this->errorResponse($response, 'Entity not found', 404);
            }

            $repository = $this->entityManager->getRepository($entityClass);
            $queryBuilder = $repository->createQueryBuilder('e');

            $searchFields = $this->getSearchFields($entityName);
            $searchConditions = [];
            foreach ($searchFields as $field) {
                $searchConditions[] = "e.{$field} LIKE :query";
            }

            if ($searchConditions) {
                $queryBuilder->where(implode(' OR ', $searchConditions))
                           ->setParameter('query', "%{$query}%")
                           ->setMaxResults($limit);

                $entities = $queryBuilder->getQuery()->getResult();

                $results = array_map(function($entity) use ($entityName) {
                    return [
                        'id' => $entity->getId(),
                        'text' => $this->getEntityDisplayText($entity, $entityName),
                        'value' => $entity->getId()
                    ];
                }, $entities);

                return $this->jsonResponse($response, ['results' => $results]);
            }

            return $this->jsonResponse($response, ['results' => []]);

        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Autocomplete failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Batch operations API
     */
    public function batch(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $entityName = $args['entity'] ?? null;
        $operation = $args['operation'] ?? null;

        if (!$this->checkRateLimit($request, 'batch') || 
            !$this->checkEntityAccess($entityName, 'update')) {
            return $this->errorResponse($response, 'Access denied', 403);
        }

        try {
            $data = $this->getRequestData($request);
            $ids = $data['ids'] ?? [];
            $params = $data['params'] ?? [];

            if (empty($ids)) {
                return $this->errorResponse($response, 'No IDs provided', 400);
            }

            $entityClass = $this->getEntityClass($entityName);
            if (!$entityClass) {
                return $this->errorResponse($response, 'Entity not found', 404);
            }

            $result = $this->batchService->execute($operation, $entityClass, $ids, $params);

            // Clear cache
            $this->cacheService->invalidateEntity($entityName);

            return $this->jsonResponse($response, $result);

        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Batch operation failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get API status and stats
     */
    public function status(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (!$this->authService->hasRole('admin')) {
            return $this->errorResponse($response, 'Admin access required', 403);
        }

        $stats = [
            'status' => 'online',
            'version' => '1.0.0',
            'cache' => $this->cacheService->getStats(),
            'database' => [
                'connected' => $this->entityManager->getConnection()->ping()
            ],
            'features' => [
                'search' => true,
                'batch_operations' => true,
                'export' => true,
                'rate_limiting' => true
            ],
            'rate_limits' => $this->rateLimits
        ];

        return $this->jsonResponse($response, $stats);
    }

    // Helper methods

    private function checkRateLimit(ServerRequestInterface $request, string $type): bool
    {
        // Simple rate limiting implementation
        $ip = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';
        $key = "rate_limit:{$type}:{$ip}:" . date('Y-m-d-H');
        
        $current = (int)$this->cacheService->get($key, fn() => 0);
        $limit = $this->rateLimits[$type] ?? $this->rateLimits['default'];
        
        if ($current >= $limit) {
            return false;
        }
        
        $this->cacheService->set($key, $current + 1, 3600);
        return true;
    }

    private function checkEntityAccess(string $entityName, string $action): bool
    {
        try {
            return $this->authService->canAccess($entityName, $action);
        } catch (\Exception $e) {
            return false;
        }
    }

    private function getEntityClass(string $entityName): ?string
    {
        $entityMap = [
            'users' => 'Turkpin\AdminKit\Entities\User',
            'roles' => 'Turkpin\AdminKit\Entities\Role',
            'permissions' => 'Turkpin\AdminKit\Entities\Permission',
        ];

        return $entityMap[$entityName] ?? null;
    }

    private function getSearchFields(string $entityName): array
    {
        $fieldMap = [
            'users' => ['name', 'email'],
            'roles' => ['name', 'description'],
            'permissions' => ['name', 'resource'],
        ];

        return $fieldMap[$entityName] ?? ['name'];
    }

    private function getEntityDisplayText($entity, string $entityName): string
    {
        if (method_exists($entity, 'getName')) {
            return $entity->getName();
        }
        
        if (method_exists($entity, 'getTitle')) {
            return $entity->getTitle();
        }
        
        return (string)$entity->getId();
    }

    private function serializeEntity($entity): array
    {
        $data = [];
        
        $reflection = new \ReflectionClass($entity);
        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($entity);
            
            if ($value instanceof \DateTime) {
                $value = $value->format('Y-m-d H:i:s');
            } elseif (is_object($value) && method_exists($value, 'getId')) {
                $value = $value->getId();
            }
            
            $data[$property->getName()] = $value;
        }
        
        return $data;
    }

    private function hydrateEntity($entity, array $data): void
    {
        foreach ($data as $key => $value) {
            $setter = 'set' . ucfirst($key);
            if (method_exists($entity, $setter)) {
                $entity->$setter($value);
            }
        }
    }

    private function getRequestData(ServerRequestInterface $request): array
    {
        $contentType = $request->getHeaderLine('Content-Type');
        
        if (strpos($contentType, 'application/json') !== false) {
            return json_decode($request->getBody()->getContents(), true) ?? [];
        }
        
        return $request->getParsedBody() ?? [];
    }

    private function jsonResponse(ResponseInterface $response, array $data, int $status = 200): ResponseInterface
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8')
                      ->withStatus($status);
    }

    private function errorResponse(ResponseInterface $response, string $message, int $status = 400): ResponseInterface
    {
        $data = [
            'error' => true,
            'message' => $message,
            'status' => $status
        ];
        
        return $this->jsonResponse($response, $data, $status);
    }
}
