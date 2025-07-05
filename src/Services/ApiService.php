<?php

declare(strict_types=1);

namespace AdminKit\Services;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;

class ApiService
{
    private EntityManagerInterface $entityManager;
    private array $config;
    private array $endpoints = [];

    public function __construct(EntityManagerInterface $entityManager, array $config = [])
    {
        $this->entityManager = $entityManager;
        $this->config = array_merge([
            'version' => 'v1',
            'prefix' => '/api',
            'auth_required' => true,
            'rate_limiting' => false,
            'cors_enabled' => true,
            'pagination_limit' => 50,
        ], $config);
    }

    /**
     * Register entity endpoints
     */
    public function registerEntityEndpoints(string $entityClass, array $options = []): self
    {
        $entityName = $this->getEntityName($entityClass);
        $basePath = $this->config['prefix'] . '/' . $this->config['version'] . '/' . strtolower($entityName);

        $defaultOptions = [
            'methods' => ['GET', 'POST', 'PUT', 'DELETE'],
            'auth_required' => $this->config['auth_required'],
            'rate_limiting' => $this->config['rate_limiting'],
            'pagination' => true,
            'filters' => [],
            'fields' => [],
        ];

        $config = array_merge($defaultOptions, $options);

        $this->endpoints[$entityName] = [
            'class' => $entityClass,
            'base_path' => $basePath,
            'config' => $config,
        ];

        return $this;
    }

    /**
     * Handle API request
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $response = new Response();
        
        try {
            // Add CORS headers
            if ($this->config['cors_enabled']) {
                $response = $this->addCorsHeaders($response);
            }

            // Handle preflight request
            if ($request->getMethod() === 'OPTIONS') {
                return $response;
            }

            // Parse request
            $path = $request->getUri()->getPath();
            $method = $request->getMethod();
            
            // Find matching endpoint
            $endpoint = $this->findEndpoint($path);
            if (!$endpoint) {
                return $this->jsonResponse($response, [
                    'error' => 'Endpoint not found'
                ], 404);
            }

            // Check authentication
            if ($endpoint['config']['auth_required']) {
                $authResult = $this->checkAuthentication($request);
                if (!$authResult['valid']) {
                    return $this->jsonResponse($response, [
                        'error' => 'Authentication required'
                    ], 401);
                }
            }

            // Check rate limiting
            if ($endpoint['config']['rate_limiting']) {
                $rateLimitResult = $this->checkRateLimit($request);
                if (!$rateLimitResult['allowed']) {
                    return $this->jsonResponse($response, [
                        'error' => 'Rate limit exceeded'
                    ], 429);
                }
            }

            // Route to appropriate handler
            return match($method) {
                'GET' => $this->handleGet($request, $endpoint),
                'POST' => $this->handlePost($request, $endpoint),
                'PUT' => $this->handlePut($request, $endpoint),
                'DELETE' => $this->handleDelete($request, $endpoint),
                default => $this->jsonResponse($response, [
                    'error' => 'Method not allowed'
                ], 405)
            };

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle GET requests
     */
    private function handleGet(ServerRequestInterface $request, array $endpoint): ResponseInterface
    {
        $response = new Response();
        $path = $request->getUri()->getPath();
        $basePath = $endpoint['base_path'];

        // Check if requesting specific resource
        if (preg_match('#^' . preg_quote($basePath) . '/(\d+)$#', $path, $matches)) {
            // Get single entity
            $id = (int)$matches[1];
            return $this->getEntity($response, $endpoint, $id);
        } else {
            // Get collection
            return $this->getEntityCollection($request, $response, $endpoint);
        }
    }

    /**
     * Handle POST requests
     */
    private function handlePost(ServerRequestInterface $request, array $endpoint): ResponseInterface
    {
        $response = new Response();
        
        if (!in_array('POST', $endpoint['config']['methods'])) {
            return $this->jsonResponse($response, [
                'error' => 'Method not allowed'
            ], 405);
        }

        $data = json_decode($request->getBody()->getContents(), true);
        
        if (!$data) {
            return $this->jsonResponse($response, [
                'error' => 'Invalid JSON data'
            ], 400);
        }

        try {
            $entity = $this->createEntity($endpoint['class'], $data);
            $this->entityManager->persist($entity);
            $this->entityManager->flush();

            return $this->jsonResponse($response, [
                'data' => $this->serializeEntity($entity, $endpoint),
                'message' => 'Entity created successfully'
            ], 201);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'error' => 'Failed to create entity',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Handle PUT requests
     */
    private function handlePut(ServerRequestInterface $request, array $endpoint): ResponseInterface
    {
        $response = new Response();
        $path = $request->getUri()->getPath();
        $basePath = $endpoint['base_path'];

        if (!preg_match('#^' . preg_quote($basePath) . '/(\d+)$#', $path, $matches)) {
            return $this->jsonResponse($response, [
                'error' => 'Entity ID required for PUT request'
            ], 400);
        }

        if (!in_array('PUT', $endpoint['config']['methods'])) {
            return $this->jsonResponse($response, [
                'error' => 'Method not allowed'
            ], 405);
        }

        $id = (int)$matches[1];
        $data = json_decode($request->getBody()->getContents(), true);

        if (!$data) {
            return $this->jsonResponse($response, [
                'error' => 'Invalid JSON data'
            ], 400);
        }

        try {
            $repository = $this->entityManager->getRepository($endpoint['class']);
            $entity = $repository->find($id);

            if (!$entity) {
                return $this->jsonResponse($response, [
                    'error' => 'Entity not found'
                ], 404);
            }

            $this->updateEntity($entity, $data);
            $this->entityManager->flush();

            return $this->jsonResponse($response, [
                'data' => $this->serializeEntity($entity, $endpoint),
                'message' => 'Entity updated successfully'
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'error' => 'Failed to update entity',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Handle DELETE requests
     */
    private function handleDelete(ServerRequestInterface $request, array $endpoint): ResponseInterface
    {
        $response = new Response();
        $path = $request->getUri()->getPath();
        $basePath = $endpoint['base_path'];

        if (!preg_match('#^' . preg_quote($basePath) . '/(\d+)$#', $path, $matches)) {
            return $this->jsonResponse($response, [
                'error' => 'Entity ID required for DELETE request'
            ], 400);
        }

        if (!in_array('DELETE', $endpoint['config']['methods'])) {
            return $this->jsonResponse($response, [
                'error' => 'Method not allowed'
            ], 405);
        }

        $id = (int)$matches[1];

        try {
            $repository = $this->entityManager->getRepository($endpoint['class']);
            $entity = $repository->find($id);

            if (!$entity) {
                return $this->jsonResponse($response, [
                    'error' => 'Entity not found'
                ], 404);
            }

            $this->entityManager->remove($entity);
            $this->entityManager->flush();

            return $this->jsonResponse($response, [
                'message' => 'Entity deleted successfully'
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'error' => 'Failed to delete entity',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get single entity
     */
    private function getEntity(ResponseInterface $response, array $endpoint, int $id): ResponseInterface
    {
        $repository = $this->entityManager->getRepository($endpoint['class']);
        $entity = $repository->find($id);

        if (!$entity) {
            return $this->jsonResponse($response, [
                'error' => 'Entity not found'
            ], 404);
        }

        return $this->jsonResponse($response, [
            'data' => $this->serializeEntity($entity, $endpoint)
        ]);
    }

    /**
     * Get entity collection
     */
    private function getEntityCollection(ServerRequestInterface $request, ResponseInterface $response, array $endpoint): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        
        $repository = $this->entityManager->getRepository($endpoint['class']);
        $queryBuilder = $repository->createQueryBuilder('e');

        // Apply filters
        if (!empty($queryParams['filter'])) {
            foreach ($queryParams['filter'] as $field => $value) {
                if (in_array($field, $endpoint['config']['filters'])) {
                    $queryBuilder->andWhere("e.$field = :$field")
                               ->setParameter($field, $value);
                }
            }
        }

        // Apply sorting
        if (!empty($queryParams['sort'])) {
            $sorts = explode(',', $queryParams['sort']);
            foreach ($sorts as $sort) {
                if (strpos($sort, '-') === 0) {
                    $field = substr($sort, 1);
                    $direction = 'DESC';
                } else {
                    $field = $sort;
                    $direction = 'ASC';
                }
                $queryBuilder->addOrderBy("e.$field", $direction);
            }
        }

        // Count total results
        $countQuery = clone $queryBuilder;
        $total = $countQuery->select('COUNT(e.id)')->getQuery()->getSingleScalarResult();

        // Apply pagination
        $page = max(1, (int)($queryParams['page'] ?? 1));
        $limit = min($this->config['pagination_limit'], (int)($queryParams['limit'] ?? 20));
        $offset = ($page - 1) * $limit;

        $queryBuilder->setFirstResult($offset)
                    ->setMaxResults($limit);

        $entities = $queryBuilder->getQuery()->getResult();

        $data = [];
        foreach ($entities as $entity) {
            $data[] = $this->serializeEntity($entity, $endpoint);
        }

        return $this->jsonResponse($response, [
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit),
            ]
        ]);
    }

    /**
     * Create entity from data
     */
    private function createEntity(string $entityClass, array $data)
    {
        $entity = new $entityClass();
        return $this->updateEntity($entity, $data);
    }

    /**
     * Update entity with data
     */
    private function updateEntity($entity, array $data)
    {
        foreach ($data as $field => $value) {
            $setter = 'set' . ucfirst($field);
            if (method_exists($entity, $setter)) {
                $entity->$setter($value);
            }
        }
        return $entity;
    }

    /**
     * Serialize entity to array
     */
    private function serializeEntity($entity, array $endpoint): array
    {
        $data = [];
        $fields = $endpoint['config']['fields'];

        if (empty($fields)) {
            // Use reflection to get all getter methods
            $reflection = new \ReflectionClass($entity);
            foreach ($reflection->getMethods() as $method) {
                if (strpos($method->getName(), 'get') === 0 && $method->isPublic()) {
                    $field = lcfirst(substr($method->getName(), 3));
                    $fields[] = $field;
                }
            }
        }

        foreach ($fields as $field) {
            $getter = 'get' . ucfirst($field);
            if (method_exists($entity, $getter)) {
                $value = $entity->$getter();
                
                if ($value instanceof \DateTime) {
                    $value = $value->format('Y-m-d H:i:s');
                } elseif (is_object($value)) {
                    $value = method_exists($value, '__toString') ? (string)$value : null;
                }
                
                $data[$field] = $value;
            }
        }

        return $data;
    }

    /**
     * Find endpoint by path
     */
    private function findEndpoint(string $path): ?array
    {
        foreach ($this->endpoints as $name => $endpoint) {
            $basePath = $endpoint['base_path'];
            
            if ($path === $basePath || strpos($path, $basePath . '/') === 0) {
                return $endpoint;
            }
        }
        
        return null;
    }

    /**
     * Get entity name from class
     */
    private function getEntityName(string $entityClass): string
    {
        $parts = explode('\\', $entityClass);
        return end($parts);
    }

    /**
     * Check authentication
     */
    private function checkAuthentication(ServerRequestInterface $request): array
    {
        // Simplified authentication check
        // In a real implementation, you would check JWT tokens, API keys, etc.
        
        $authHeader = $request->getHeader('Authorization');
        
        if (empty($authHeader)) {
            return ['valid' => false, 'user' => null];
        }

        // Basic token validation
        $token = str_replace('Bearer ', '', $authHeader[0]);
        
        // This is a placeholder - implement your actual authentication logic
        if ($token === 'valid-api-token') {
            return ['valid' => true, 'user' => ['id' => 1, 'name' => 'API User']];
        }

        return ['valid' => false, 'user' => null];
    }

    /**
     * Check rate limiting
     */
    private function checkRateLimit(ServerRequestInterface $request): array
    {
        // Simplified rate limiting
        // In a real implementation, you would use Redis or database to track requests
        
        return ['allowed' => true, 'remaining' => 100];
    }

    /**
     * Add CORS headers
     */
    private function addCorsHeaders(ResponseInterface $response): ResponseInterface
    {
        return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
            ->withHeader('Access-Control-Max-Age', '86400');
    }

    /**
     * Create JSON response
     */
    private function jsonResponse(ResponseInterface $response, array $data, int $status = 200): ResponseInterface
    {
        $response->getBody()->write(json_encode($data));
        
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }

    /**
     * Get API documentation
     */
    public function getDocumentation(): array
    {
        $docs = [
            'version' => $this->config['version'],
            'base_url' => $this->config['prefix'] . '/' . $this->config['version'],
            'endpoints' => [],
        ];

        foreach ($this->endpoints as $name => $endpoint) {
            $docs['endpoints'][$name] = [
                'base_path' => $endpoint['base_path'],
                'methods' => $endpoint['config']['methods'],
                'auth_required' => $endpoint['config']['auth_required'],
                'operations' => [
                    'GET' => [
                        'collection' => $endpoint['base_path'],
                        'single' => $endpoint['base_path'] . '/{id}',
                    ],
                    'POST' => $endpoint['base_path'],
                    'PUT' => $endpoint['base_path'] . '/{id}',
                    'DELETE' => $endpoint['base_path'] . '/{id}',
                ],
            ];
        }

        return $docs;
    }

    /**
     * Get registered endpoints
     */
    public function getEndpoints(): array
    {
        return $this->endpoints;
    }
}
