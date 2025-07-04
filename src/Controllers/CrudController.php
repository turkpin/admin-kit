<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Turkpin\AdminKit\Services\AuthService;
use Turkpin\AdminKit\Services\SmartyService;
use Doctrine\ORM\EntityManagerInterface;

class CrudController
{
    private AuthService $authService;
    private SmartyService $smartyService;
    private EntityManagerInterface $entityManager;
    private array $config;
    private array $entities;

    public function __construct(
        AuthService $authService,
        SmartyService $smartyService,
        EntityManagerInterface $entityManager,
        array $config,
        array $entities = []
    ) {
        $this->authService = $authService;
        $this->smartyService = $smartyService;
        $this->entityManager = $entityManager;
        $this->config = $config;
        $this->entities = $entities;
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $entityName = $args['entity'] ?? $request->getAttribute('entity');
        $entityConfig = $this->getEntityConfig($entityName);
        
        if (!$entityConfig) {
            return $response->withStatus(404);
        }

        // Yetki kontrolü
        if (!$this->authService->canAccess($entityName, 'index')) {
            return $response->withStatus(403);
        }

        $queryParams = $request->getQueryParams();
        $page = max(1, (int)($queryParams['page'] ?? 1));
        $limit = $entityConfig['pagination'] ?? 20;
        $search = trim($queryParams['search'] ?? '');
        $filters = $this->extractFilters($queryParams, $entityConfig);

        try {
            $repository = $this->entityManager->getRepository($entityConfig['class']);
            $queryBuilder = $repository->createQueryBuilder('e');

            // Search functionality
            if ($search && $entityConfig['searchable']) {
                $this->applySearch($queryBuilder, $search, $entityConfig);
            }

            // Apply filters
            $this->applyFilters($queryBuilder, $filters, $entityConfig);

            // Get total count for pagination
            $totalQuery = clone $queryBuilder;
            $totalQuery->select('COUNT(e.id)');
            $total = $totalQuery->getQuery()->getSingleScalarResult();

            // Apply pagination
            $offset = ($page - 1) * $limit;
            $queryBuilder->setFirstResult($offset)->setMaxResults($limit);

            // Apply sorting
            $sortField = $queryParams['sort'] ?? 'id';
            $sortDirection = strtoupper($queryParams['direction'] ?? 'DESC');
            if (in_array($sortDirection, ['ASC', 'DESC'])) {
                $queryBuilder->orderBy("e.{$sortField}", $sortDirection);
            }

            $entities = $queryBuilder->getQuery()->getResult();

            $pagination = [
                'current_page' => $page,
                'total_pages' => ceil($total / $limit),
                'total_items' => $total,
                'per_page' => $limit,
                'has_prev' => $page > 1,
                'has_next' => $page < ceil($total / $limit),
                'prev_page' => max(1, $page - 1),
                'next_page' => min(ceil($total / $limit), $page + 1),
            ];

            $data = [
                'page_title' => $entityConfig['title'],
                'entity_name' => $entityName,
                'entity_config' => $entityConfig,
                'entities' => $entities,
                'pagination' => $pagination,
                'search' => $search,
                'filters' => $filters,
                'breadcrumbs' => $this->getBreadcrumbs($entityName, $entityConfig['title']),
                'actions' => $this->getAvailableActions($entityName, $entityConfig),
            ];

            $html = $this->smartyService->render('crud/index.tpl', $data);
            $response->getBody()->write($html);

            return $response->withHeader('Content-Type', 'text/html');

        } catch (\Exception $e) {
            error_log("CRUD index error: " . $e->getMessage());
            return $response->withStatus(500);
        }
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $entityName = $args['entity'] ?? $request->getAttribute('entity');
        $id = $args['id'] ?? $request->getAttribute('id');
        $entityConfig = $this->getEntityConfig($entityName);

        if (!$entityConfig || !$id) {
            return $response->withStatus(404);
        }

        // Yetki kontrolü
        if (!$this->authService->canAccess($entityName, 'show')) {
            return $response->withStatus(403);
        }

        try {
            $repository = $this->entityManager->getRepository($entityConfig['class']);
            $entity = $repository->find($id);

            if (!$entity) {
                return $response->withStatus(404);
            }

            $data = [
                'page_title' => $entityConfig['title'] . ' Detayı',
                'entity_name' => $entityName,
                'entity_config' => $entityConfig,
                'entity' => $entity,
                'breadcrumbs' => $this->getBreadcrumbs($entityName, $entityConfig['title'], 'Detay'),
                'actions' => $this->getAvailableActions($entityName, $entityConfig, $entity),
            ];

            $html = $this->smartyService->render('crud/show.tpl', $data);
            $response->getBody()->write($html);

            return $response->withHeader('Content-Type', 'text/html');

        } catch (\Exception $e) {
            error_log("CRUD show error: " . $e->getMessage());
            return $response->withStatus(500);
        }
    }

    public function new(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $entityName = $args['entity'] ?? $request->getAttribute('entity');
        $entityConfig = $this->getEntityConfig($entityName);

        if (!$entityConfig) {
            return $response->withStatus(404);
        }

        // Yetki kontrolü
        if (!$this->authService->canAccess($entityName, 'new')) {
            return $response->withStatus(403);
        }

        $data = [
            'page_title' => 'Yeni ' . $entityConfig['title'],
            'entity_name' => $entityName,
            'entity_config' => $entityConfig,
            'entity' => null,
            'errors' => [],
            'breadcrumbs' => $this->getBreadcrumbs($entityName, $entityConfig['title'], 'Yeni'),
            'form_action' => $this->config['route_prefix'] . '/' . $entityName . '/new',
            'form_method' => 'POST',
        ];

        $html = $this->smartyService->render('crud/new.tpl', $data);
        $response->getBody()->write($html);

        return $response->withHeader('Content-Type', 'text/html');
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $entityName = $args['entity'] ?? $request->getAttribute('entity');
        $entityConfig = $this->getEntityConfig($entityName);

        if (!$entityConfig) {
            return $response->withStatus(404);
        }

        // Yetki kontrolü
        if (!$this->authService->canAccess($entityName, 'create')) {
            return $response->withStatus(403);
        }

        $parsedBody = $request->getParsedBody();
        
        try {
            $entityClass = $entityConfig['class'];
            $entity = new $entityClass();

            // Form verilerini entity'ye ata
            $errors = $this->populateEntity($entity, $parsedBody, $entityConfig);

            if (!empty($errors)) {
                return $this->showCreateFormWithErrors($entityName, $entityConfig, $errors, $parsedBody);
            }

            // Entity'yi kaydet
            $this->entityManager->persist($entity);
            $this->entityManager->flush();

            // Başarı mesajı ile listeleme sayfasına yönlendir
            $redirectUrl = $this->config['route_prefix'] . '/' . $entityName . '?success=created';
            return $response->withHeader('Location', $redirectUrl)->withStatus(302);

        } catch (\Exception $e) {
            error_log("CRUD create error: " . $e->getMessage());
            $errors = ['Kayıt sırasında bir hata oluştu: ' . $e->getMessage()];
            return $this->showCreateFormWithErrors($entityName, $entityConfig, $errors, $parsedBody);
        }
    }

    public function edit(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $entityName = $args['entity'] ?? $request->getAttribute('entity');
        $id = $args['id'] ?? $request->getAttribute('id');
        $entityConfig = $this->getEntityConfig($entityName);

        if (!$entityConfig || !$id) {
            return $response->withStatus(404);
        }

        // Yetki kontrolü
        if (!$this->authService->canAccess($entityName, 'edit')) {
            return $response->withStatus(403);
        }

        try {
            $repository = $this->entityManager->getRepository($entityConfig['class']);
            $entity = $repository->find($id);

            if (!$entity) {
                return $response->withStatus(404);
            }

            $data = [
                'page_title' => $entityConfig['title'] . ' Düzenle',
                'entity_name' => $entityName,
                'entity_config' => $entityConfig,
                'entity' => $entity,
                'errors' => [],
                'breadcrumbs' => $this->getBreadcrumbs($entityName, $entityConfig['title'], 'Düzenle'),
                'form_action' => $this->config['route_prefix'] . '/' . $entityName . '/' . $id . '/edit',
                'form_method' => 'POST',
            ];

            $html = $this->smartyService->render('crud/edit.tpl', $data);
            $response->getBody()->write($html);

            return $response->withHeader('Content-Type', 'text/html');

        } catch (\Exception $e) {
            error_log("CRUD edit error: " . $e->getMessage());
            return $response->withStatus(500);
        }
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $entityName = $args['entity'] ?? $request->getAttribute('entity');
        $id = $args['id'] ?? $request->getAttribute('id');
        $entityConfig = $this->getEntityConfig($entityName);

        if (!$entityConfig || !$id) {
            return $response->withStatus(404);
        }

        // Yetki kontrolü
        if (!$this->authService->canAccess($entityName, 'update')) {
            return $response->withStatus(403);
        }

        $parsedBody = $request->getParsedBody();

        try {
            $repository = $this->entityManager->getRepository($entityConfig['class']);
            $entity = $repository->find($id);

            if (!$entity) {
                return $response->withStatus(404);
            }

            // Form verilerini entity'ye ata
            $errors = $this->populateEntity($entity, $parsedBody, $entityConfig);

            if (!empty($errors)) {
                return $this->showEditFormWithErrors($entityName, $entityConfig, $entity, $errors);
            }

            // Entity'yi güncelle
            $this->entityManager->flush();

            // Başarı mesajı ile listeleme sayfasına yönlendir
            $redirectUrl = $this->config['route_prefix'] . '/' . $entityName . '?success=updated';
            return $response->withHeader('Location', $redirectUrl)->withStatus(302);

        } catch (\Exception $e) {
            error_log("CRUD update error: " . $e->getMessage());
            $errors = ['Güncelleme sırasında bir hata oluştu: ' . $e->getMessage()];
            
            try {
                $repository = $this->entityManager->getRepository($entityConfig['class']);
                $entity = $repository->find($id);
                return $this->showEditFormWithErrors($entityName, $entityConfig, $entity, $errors);
            } catch (\Exception $e2) {
                return $response->withStatus(500);
            }
        }
    }

    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $entityName = $args['entity'] ?? $request->getAttribute('entity');
        $id = $args['id'] ?? $request->getAttribute('id');
        $entityConfig = $this->getEntityConfig($entityName);

        if (!$entityConfig || !$id) {
            return $response->withStatus(404);
        }

        // Yetki kontrolü
        if (!$this->authService->canAccess($entityName, 'delete')) {
            return $response->withStatus(403);
        }

        try {
            $repository = $this->entityManager->getRepository($entityConfig['class']);
            $entity = $repository->find($id);

            if (!$entity) {
                return $response->withStatus(404);
            }

            $this->entityManager->remove($entity);
            $this->entityManager->flush();

            // Başarı mesajı ile listeleme sayfasına yönlendir
            $redirectUrl = $this->config['route_prefix'] . '/' . $entityName . '?success=deleted';
            return $response->withHeader('Location', $redirectUrl)->withStatus(302);

        } catch (\Exception $e) {
            error_log("CRUD delete error: " . $e->getMessage());
            $redirectUrl = $this->config['route_prefix'] . '/' . $entityName . '?error=delete_failed';
            return $response->withHeader('Location', $redirectUrl)->withStatus(302);
        }
    }

    private function getEntityConfig(string $entityName): ?array
    {
        return $this->entities[$entityName] ?? null;
    }

    private function extractFilters(array $queryParams, array $entityConfig): array
    {
        $filters = [];
        $availableFilters = $entityConfig['filters'] ?? [];

        foreach ($availableFilters as $filterField) {
            if (isset($queryParams[$filterField]) && $queryParams[$filterField] !== '') {
                $filters[$filterField] = $queryParams[$filterField];
            }
        }

        return $filters;
    }

    private function applySearch($queryBuilder, string $search, array $entityConfig): void
    {
        $searchFields = [];
        foreach ($entityConfig['fields'] as $fieldName => $fieldConfig) {
            if (in_array($fieldConfig['type'] ?? 'text', ['text', 'email', 'textarea'])) {
                $searchFields[] = "e.{$fieldName}";
            }
        }

        if (!empty($searchFields)) {
            $orConditions = [];
            foreach ($searchFields as $i => $field) {
                $orConditions[] = "{$field} LIKE :search{$i}";
                $queryBuilder->setParameter("search{$i}", "%{$search}%");
            }
            $queryBuilder->andWhere(implode(' OR ', $orConditions));
        }
    }

    private function applyFilters($queryBuilder, array $filters, array $entityConfig): void
    {
        foreach ($filters as $field => $value) {
            $fieldConfig = $entityConfig['fields'][$field] ?? [];
            $fieldType = $fieldConfig['type'] ?? 'text';

            switch ($fieldType) {
                case 'boolean':
                    $queryBuilder->andWhere("e.{$field} = :{$field}")
                               ->setParameter($field, (bool)$value);
                    break;
                case 'date':
                case 'datetime':
                    $queryBuilder->andWhere("DATE(e.{$field}) = :{$field}")
                               ->setParameter($field, $value);
                    break;
                default:
                    $queryBuilder->andWhere("e.{$field} LIKE :{$field}")
                               ->setParameter($field, "%{$value}%");
            }
        }
    }

    private function populateEntity($entity, array $data, array $entityConfig): array
    {
        $errors = [];

        foreach ($entityConfig['fields'] as $fieldName => $fieldConfig) {
            if (!isset($data[$fieldName])) {
                continue;
            }

            $value = $data[$fieldName];
            $setter = 'set' . ucfirst($fieldName);

            if (!method_exists($entity, $setter)) {
                continue;
            }

            try {
                // Field type'a göre veri dönüşümü
                $convertedValue = $this->convertFieldValue($value, $fieldConfig);
                $entity->$setter($convertedValue);
            } catch (\Exception $e) {
                $errors[] = "'{$fieldName}' alanında hata: " . $e->getMessage();
            }
        }

        return $errors;
    }

    private function convertFieldValue($value, array $fieldConfig)
    {
        $type = $fieldConfig['type'] ?? 'text';

        switch ($type) {
            case 'boolean':
                return (bool)$value;
            case 'number':
                return is_numeric($value) ? (int)$value : null;
            case 'date':
                return $value ? new \DateTime($value) : null;
            case 'datetime':
                return $value ? new \DateTime($value) : null;
            default:
                return $value;
        }
    }

    private function getBreadcrumbs(string $entityName, string $entityTitle, string $action = null): array
    {
        $breadcrumbs = [
            ['title' => 'Dashboard', 'url' => $this->config['route_prefix']],
            ['title' => $entityTitle, 'url' => $this->config['route_prefix'] . '/' . $entityName],
        ];

        if ($action) {
            $breadcrumbs[] = ['title' => $action, 'url' => '#'];
        }

        return $breadcrumbs;
    }

    private function getAvailableActions(string $entityName, array $entityConfig, $entity = null): array
    {
        $actions = [];
        $availableActions = $entityConfig['actions'] ?? [];

        foreach ($availableActions as $action) {
            if ($this->authService->canAccess($entityName, $action)) {
                $actions[] = $this->getActionConfig($action, $entityName, $entity);
            }
        }

        return $actions;
    }

    private function getActionConfig(string $action, string $entityName, $entity = null): array
    {
        $baseUrl = $this->config['route_prefix'] . '/' . $entityName;
        $entityId = $entity ? $entity->getId() : null;

        $actions = [
            'new' => [
                'title' => 'Yeni Ekle',
                'url' => $baseUrl . '/new',
                'icon' => 'plus',
                'class' => 'btn-primary'
            ],
            'show' => [
                'title' => 'Görüntüle',
                'url' => $baseUrl . '/' . $entityId,
                'icon' => 'eye',
                'class' => 'btn-secondary'
            ],
            'edit' => [
                'title' => 'Düzenle',
                'url' => $baseUrl . '/' . $entityId . '/edit',
                'icon' => 'edit',
                'class' => 'btn-warning'
            ],
            'delete' => [
                'title' => 'Sil',
                'url' => $baseUrl . '/' . $entityId,
                'icon' => 'delete',
                'class' => 'btn-danger',
                'method' => 'DELETE',
                'confirm' => 'Bu kaydı silmek istediğinizden emin misiniz?'
            ]
        ];

        return $actions[$action] ?? [];
    }

    private function showCreateFormWithErrors(string $entityName, array $entityConfig, array $errors, array $data): ResponseInterface
    {
        $templateData = [
            'page_title' => 'Yeni ' . $entityConfig['title'],
            'entity_name' => $entityName,
            'entity_config' => $entityConfig,
            'entity' => null,
            'errors' => $errors,
            'form_data' => $data,
            'breadcrumbs' => $this->getBreadcrumbs($entityName, $entityConfig['title'], 'Yeni'),
            'form_action' => $this->config['route_prefix'] . '/' . $entityName . '/new',
            'form_method' => 'POST',
        ];

        $html = $this->smartyService->render('crud/new.tpl', $templateData);
        
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    private function showEditFormWithErrors(string $entityName, array $entityConfig, $entity, array $errors): ResponseInterface
    {
        $templateData = [
            'page_title' => $entityConfig['title'] . ' Düzenle',
            'entity_name' => $entityName,
            'entity_config' => $entityConfig,
            'entity' => $entity,
            'errors' => $errors,
            'breadcrumbs' => $this->getBreadcrumbs($entityName, $entityConfig['title'], 'Düzenle'),
            'form_action' => $this->config['route_prefix'] . '/' . $entityName . '/' . $entity->getId() . '/edit',
            'form_method' => 'POST',
        ];

        $html = $this->smartyService->render('crud/edit.tpl', $templateData);
        
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }
}
