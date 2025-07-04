<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Turkpin\AdminKit\Services\AuthService;
use Turkpin\AdminKit\Services\SmartyService;
use Turkpin\AdminKit\Services\ValidationService;
use Turkpin\AdminKit\Entities\Role;
use Turkpin\AdminKit\Entities\Permission;
use Doctrine\ORM\EntityManagerInterface;

class RoleController
{
    private AuthService $authService;
    private SmartyService $smartyService;
    private ValidationService $validationService;
    private EntityManagerInterface $entityManager;
    private array $config;

    public function __construct(
        AuthService $authService,
        SmartyService $smartyService,
        ValidationService $validationService,
        EntityManagerInterface $entityManager,
        array $config
    ) {
        $this->authService = $authService;
        $this->smartyService = $smartyService;
        $this->validationService = $validationService;
        $this->entityManager = $entityManager;
        $this->config = $config;
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // Yetki kontrolü
        if (!$this->authService->canAccess('role', 'index')) {
            return $response->withStatus(403);
        }

        $queryParams = $request->getQueryParams();
        $page = max(1, (int)($queryParams['page'] ?? 1));
        $limit = 20;
        $search = trim($queryParams['search'] ?? '');

        try {
            $repository = $this->entityManager->getRepository(Role::class);
            $queryBuilder = $repository->createQueryBuilder('r')
                ->leftJoin('r.permissions', 'p')
                ->leftJoin('r.users', 'u')
                ->addSelect('p', 'u');

            if ($search) {
                $queryBuilder->where('r.name LIKE :search OR r.description LIKE :search')
                           ->setParameter('search', "%{$search}%");
            }

            // Get total count
            $totalQuery = clone $queryBuilder;
            $totalQuery->select('COUNT(r.id)');
            $total = $totalQuery->getQuery()->getSingleScalarResult();

            // Apply pagination
            $offset = ($page - 1) * $limit;
            $queryBuilder->setFirstResult($offset)
                        ->setMaxResults($limit)
                        ->orderBy('r.id', 'DESC');

            $roles = $queryBuilder->getQuery()->getResult();

            $pagination = [
                'current_page' => $page,
                'total_pages' => ceil($total / $limit),
                'total_items' => $total,
                'per_page' => $limit,
                'has_prev' => $page > 1,
                'has_next' => $page < ceil($total / $limit),
            ];

            $data = [
                'page_title' => 'Role Management',
                'roles' => $roles,
                'pagination' => $pagination,
                'search' => $search,
                'breadcrumbs' => [
                    ['title' => 'Dashboard', 'url' => '/admin'],
                    ['title' => 'Roles', 'url' => '#']
                ],
                'can_create' => $this->authService->canAccess('role', 'create'),
                'can_edit' => $this->authService->canAccess('role', 'edit'),
                'can_delete' => $this->authService->canAccess('role', 'delete'),
            ];

            $html = $this->smartyService->render('admin/roles/index.tpl', $data);
            $response->getBody()->write($html);

            return $response->withHeader('Content-Type', 'text/html');

        } catch (\Exception $e) {
            error_log("Role index error: " . $e->getMessage());
            return $response->withStatus(500);
        }
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int)$args['id'];

        if (!$this->authService->canAccess('role', 'show')) {
            return $response->withStatus(403);
        }

        try {
            $role = $this->entityManager->getRepository(Role::class)->find($id);

            if (!$role) {
                return $response->withStatus(404);
            }

            $data = [
                'page_title' => 'Role Details: ' . $role->getName(),
                'role' => $role,
                'breadcrumbs' => [
                    ['title' => 'Dashboard', 'url' => '/admin'],
                    ['title' => 'Roles', 'url' => '/admin/roles'],
                    ['title' => $role->getName(), 'url' => '#']
                ],
                'can_edit' => $this->authService->canAccess('role', 'edit'),
                'can_delete' => $this->authService->canAccess('role', 'delete'),
            ];

            $html = $this->smartyService->render('admin/roles/show.tpl', $data);
            $response->getBody()->write($html);

            return $response->withHeader('Content-Type', 'text/html');

        } catch (\Exception $e) {
            error_log("Role show error: " . $e->getMessage());
            return $response->withStatus(500);
        }
    }

    public function new(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (!$this->authService->canAccess('role', 'new')) {
            return $response->withStatus(403);
        }

        $permissions = $this->entityManager->getRepository(Permission::class)->findAll();

        $data = [
            'page_title' => 'Create New Role',
            'permissions' => $permissions,
            'errors' => [],
            'form_data' => [],
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'url' => '/admin'],
                ['title' => 'Roles', 'url' => '/admin/roles'],
                ['title' => 'New Role', 'url' => '#']
            ]
        ];

        $html = $this->smartyService->render('admin/roles/new.tpl', $data);
        $response->getBody()->write($html);

        return $response->withHeader('Content-Type', 'text/html');
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (!$this->authService->canAccess('role', 'create')) {
            return $response->withStatus(403);
        }

        $data = $request->getParsedBody();

        // Validation
        $this->validationService->setRules([
            'name' => ['required', 'min_length:2', 'max_length:255'],
            'description' => ['max_length:500'],
        ]);

        if (!$this->validationService->validate($data)) {
            return $this->showNewFormWithErrors($data, $this->validationService->getErrors());
        }

        try {
            $role = $this->authService->createRole(
                $data['name'],
                $data['description'] ?? null
            );

            // Assign permissions
            if (!empty($data['permissions'])) {
                foreach ($data['permissions'] as $permissionId) {
                    $permission = $this->entityManager->getRepository(Permission::class)->find($permissionId);
                    if ($permission) {
                        $this->authService->assignPermissionToRole($role, $permission);
                    }
                }
            }

            return $response->withHeader('Location', '/admin/roles?success=created')->withStatus(302);

        } catch (\Exception $e) {
            error_log("Role creation error: " . $e->getMessage());
            return $this->showNewFormWithErrors($data, ['An error occurred while creating the role.']);
        }
    }

    public function edit(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int)$args['id'];

        if (!$this->authService->canAccess('role', 'edit')) {
            return $response->withStatus(403);
        }

        try {
            $role = $this->entityManager->getRepository(Role::class)->find($id);

            if (!$role) {
                return $response->withStatus(404);
            }

            $permissions = $this->entityManager->getRepository(Permission::class)->findAll();

            $data = [
                'page_title' => 'Edit Role: ' . $role->getName(),
                'role' => $role,
                'permissions' => $permissions,
                'errors' => [],
                'breadcrumbs' => [
                    ['title' => 'Dashboard', 'url' => '/admin'],
                    ['title' => 'Roles', 'url' => '/admin/roles'],
                    ['title' => 'Edit Role', 'url' => '#']
                ]
            ];

            $html = $this->smartyService->render('admin/roles/edit.tpl', $data);
            $response->getBody()->write($html);

            return $response->withHeader('Content-Type', 'text/html');

        } catch (\Exception $e) {
            error_log("Role edit error: " . $e->getMessage());
            return $response->withStatus(500);
        }
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int)$args['id'];

        if (!$this->authService->canAccess('role', 'update')) {
            return $response->withStatus(403);
        }

        $data = $request->getParsedBody();

        try {
            $role = $this->entityManager->getRepository(Role::class)->find($id);

            if (!$role) {
                return $response->withStatus(404);
            }

            // Validation
            $this->validationService->setRules([
                'name' => ['required', 'min_length:2', 'max_length:255'],
                'description' => ['max_length:500'],
            ]);

            if (!$this->validationService->validate($data)) {
                return $this->showEditFormWithErrors($role, $this->validationService->getErrors());
            }

            // Update role
            $role->setName($data['name']);
            $role->setDescription($data['description'] ?? null);
            $role->setUpdatedAt(new \DateTime());

            // Update permissions
            $role->getPermissions()->clear();
            if (!empty($data['permissions'])) {
                foreach ($data['permissions'] as $permissionId) {
                    $permission = $this->entityManager->getRepository(Permission::class)->find($permissionId);
                    if ($permission) {
                        $this->authService->assignPermissionToRole($role, $permission);
                    }
                }
            }

            $this->entityManager->flush();

            return $response->withHeader('Location', '/admin/roles?success=updated')->withStatus(302);

        } catch (\Exception $e) {
            error_log("Role update error: " . $e->getMessage());
            $role = $this->entityManager->getRepository(Role::class)->find($id);
            return $this->showEditFormWithErrors($role, ['An error occurred while updating the role.']);
        }
    }

    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int)$args['id'];

        if (!$this->authService->canAccess('role', 'delete')) {
            return $response->withStatus(403);
        }

        try {
            $role = $this->entityManager->getRepository(Role::class)->find($id);

            if (!$role) {
                return $response->withStatus(404);
            }

            // Prevent deleting system roles
            $systemRoles = ['super_admin', 'admin'];
            if (in_array($role->getName(), $systemRoles)) {
                return $response->withHeader('Location', '/admin/roles?error=cannot_delete_system_role')->withStatus(302);
            }

            $this->entityManager->remove($role);
            $this->entityManager->flush();

            return $response->withHeader('Location', '/admin/roles?success=deleted')->withStatus(302);

        } catch (\Exception $e) {
            error_log("Role deletion error: " . $e->getMessage());
            return $response->withHeader('Location', '/admin/roles?error=delete_failed')->withStatus(302);
        }
    }

    private function showNewFormWithErrors(array $formData, array $errors): ResponseInterface
    {
        $permissions = $this->entityManager->getRepository(Permission::class)->findAll();

        $data = [
            'page_title' => 'Create New Role',
            'permissions' => $permissions,
            'errors' => $errors,
            'form_data' => $formData,
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'url' => '/admin'],
                ['title' => 'Roles', 'url' => '/admin/roles'],
                ['title' => 'New Role', 'url' => '#']
            ]
        ];

        $html = $this->smartyService->render('admin/roles/new.tpl', $data);
        
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    private function showEditFormWithErrors(Role $role, array $errors): ResponseInterface
    {
        $permissions = $this->entityManager->getRepository(Permission::class)->findAll();

        $data = [
            'page_title' => 'Edit Role: ' . $role->getName(),
            'role' => $role,
            'permissions' => $permissions,
            'errors' => $errors,
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'url' => '/admin'],
                ['title' => 'Roles', 'url' => '/admin/roles'],
                ['title' => 'Edit Role', 'url' => '#']
            ]
        ];

        $html = $this->smartyService->render('admin/roles/edit.tpl', $data);
        
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }
}
