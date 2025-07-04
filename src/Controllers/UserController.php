<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Turkpin\AdminKit\Services\AuthService;
use Turkpin\AdminKit\Services\SmartyService;
use Turkpin\AdminKit\Services\ValidationService;
use Turkpin\AdminKit\Entities\User;
use Turkpin\AdminKit\Entities\Role;
use Doctrine\ORM\EntityManagerInterface;

class UserController
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
        if (!$this->authService->canAccess('user', 'index')) {
            return $response->withStatus(403);
        }

        $queryParams = $request->getQueryParams();
        $page = max(1, (int)($queryParams['page'] ?? 1));
        $limit = 20;
        $search = trim($queryParams['search'] ?? '');

        try {
            $repository = $this->entityManager->getRepository(User::class);
            $queryBuilder = $repository->createQueryBuilder('u')
                ->leftJoin('u.roles', 'r')
                ->addSelect('r');

            // Search functionality
            if ($search) {
                $queryBuilder->where('u.name LIKE :search OR u.email LIKE :search')
                           ->setParameter('search', "%{$search}%");
            }

            // Get total count
            $totalQuery = clone $queryBuilder;
            $totalQuery->select('COUNT(u.id)');
            $total = $totalQuery->getQuery()->getSingleScalarResult();

            // Apply pagination
            $offset = ($page - 1) * $limit;
            $queryBuilder->setFirstResult($offset)
                        ->setMaxResults($limit)
                        ->orderBy('u.id', 'DESC');

            $users = $queryBuilder->getQuery()->getResult();

            $pagination = [
                'current_page' => $page,
                'total_pages' => ceil($total / $limit),
                'total_items' => $total,
                'per_page' => $limit,
                'has_prev' => $page > 1,
                'has_next' => $page < ceil($total / $limit),
            ];

            $data = [
                'page_title' => 'User Management',
                'users' => $users,
                'pagination' => $pagination,
                'search' => $search,
                'breadcrumbs' => [
                    ['title' => 'Dashboard', 'url' => '/admin'],
                    ['title' => 'Users', 'url' => '#']
                ],
                'can_create' => $this->authService->canAccess('user', 'create'),
                'can_edit' => $this->authService->canAccess('user', 'edit'),
                'can_delete' => $this->authService->canAccess('user', 'delete'),
            ];

            $html = $this->smartyService->render('admin/users/index.tpl', $data);
            $response->getBody()->write($html);

            return $response->withHeader('Content-Type', 'text/html');

        } catch (\Exception $e) {
            error_log("User index error: " . $e->getMessage());
            return $response->withStatus(500);
        }
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int)$args['id'];

        // Yetki kontrolü
        if (!$this->authService->canAccess('user', 'show')) {
            return $response->withStatus(403);
        }

        try {
            $user = $this->entityManager->getRepository(User::class)->find($id);

            if (!$user) {
                return $response->withStatus(404);
            }

            $data = [
                'page_title' => 'User Details: ' . $user->getName(),
                'user' => $user,
                'breadcrumbs' => [
                    ['title' => 'Dashboard', 'url' => '/admin'],
                    ['title' => 'Users', 'url' => '/admin/users'],
                    ['title' => $user->getName(), 'url' => '#']
                ],
                'can_edit' => $this->authService->canAccess('user', 'edit'),
                'can_delete' => $this->authService->canAccess('user', 'delete'),
            ];

            $html = $this->smartyService->render('admin/users/show.tpl', $data);
            $response->getBody()->write($html);

            return $response->withHeader('Content-Type', 'text/html');

        } catch (\Exception $e) {
            error_log("User show error: " . $e->getMessage());
            return $response->withStatus(500);
        }
    }

    public function new(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // Yetki kontrolü
        if (!$this->authService->canAccess('user', 'new')) {
            return $response->withStatus(403);
        }

        $roles = $this->entityManager->getRepository(Role::class)->findAll();

        $data = [
            'page_title' => 'Create New User',
            'roles' => $roles,
            'errors' => [],
            'form_data' => [],
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'url' => '/admin'],
                ['title' => 'Users', 'url' => '/admin/users'],
                ['title' => 'New User', 'url' => '#']
            ]
        ];

        $html = $this->smartyService->render('admin/users/new.tpl', $data);
        $response->getBody()->write($html);

        return $response->withHeader('Content-Type', 'text/html');
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // Yetki kontrolü
        if (!$this->authService->canAccess('user', 'create')) {
            return $response->withStatus(403);
        }

        $data = $request->getParsedBody();

        // Validation rules
        $this->validationService->setRules([
            'name' => ['required', 'min_length:2', 'max_length:255'],
            'email' => ['required', 'email', 'unique_email'],
            'password' => ['required', 'min_length:6'],
            'password_confirm' => ['required', 'matches:password'],
        ]);

        if (!$this->validationService->validate($data)) {
            return $this->showNewFormWithErrors($data, $this->validationService->getErrors());
        }

        try {
            // Create user
            $user = $this->authService->createUser([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'isActive' => isset($data['is_active'])
            ]);

            // Assign roles
            if (!empty($data['roles'])) {
                foreach ($data['roles'] as $roleId) {
                    $role = $this->entityManager->getRepository(Role::class)->find($roleId);
                    if ($role) {
                        $this->authService->assignRole($user, $role);
                    }
                }
            }

            return $response->withHeader('Location', '/admin/users?success=created')->withStatus(302);

        } catch (\Exception $e) {
            error_log("User creation error: " . $e->getMessage());
            return $this->showNewFormWithErrors($data, ['An error occurred while creating the user.']);
        }
    }

    public function edit(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int)$args['id'];

        // Yetki kontrolü
        if (!$this->authService->canAccess('user', 'edit')) {
            return $response->withStatus(403);
        }

        try {
            $user = $this->entityManager->getRepository(User::class)->find($id);

            if (!$user) {
                return $response->withStatus(404);
            }

            $roles = $this->entityManager->getRepository(Role::class)->findAll();

            $data = [
                'page_title' => 'Edit User: ' . $user->getName(),
                'user' => $user,
                'roles' => $roles,
                'errors' => [],
                'breadcrumbs' => [
                    ['title' => 'Dashboard', 'url' => '/admin'],
                    ['title' => 'Users', 'url' => '/admin/users'],
                    ['title' => 'Edit User', 'url' => '#']
                ]
            ];

            $html = $this->smartyService->render('admin/users/edit.tpl', $data);
            $response->getBody()->write($html);

            return $response->withHeader('Content-Type', 'text/html');

        } catch (\Exception $e) {
            error_log("User edit error: " . $e->getMessage());
            return $response->withStatus(500);
        }
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int)$args['id'];

        // Yetki kontrolü
        if (!$this->authService->canAccess('user', 'update')) {
            return $response->withStatus(403);
        }

        $data = $request->getParsedBody();

        try {
            $user = $this->entityManager->getRepository(User::class)->find($id);

            if (!$user) {
                return $response->withStatus(404);
            }

            // Validation rules
            $rules = [
                'name' => ['required', 'min_length:2', 'max_length:255'],
                'email' => ['required', 'email'],
            ];

            if (!empty($data['password'])) {
                $rules['password'] = ['min_length:6'];
                $rules['password_confirm'] = ['matches:password'];
            }

            $this->validationService->setRules($rules);

            if (!$this->validationService->validate($data)) {
                return $this->showEditFormWithErrors($user, $this->validationService->getErrors());
            }

            // Update user
            $updateData = [
                'name' => $data['name'],
                'email' => $data['email'],
                'isActive' => isset($data['is_active'])
            ];

            if (!empty($data['password'])) {
                $updateData['password'] = $data['password'];
            }

            $this->authService->updateUser($user, $updateData);

            // Update roles
            $user->getRoles()->clear();
            if (!empty($data['roles'])) {
                foreach ($data['roles'] as $roleId) {
                    $role = $this->entityManager->getRepository(Role::class)->find($roleId);
                    if ($role) {
                        $this->authService->assignRole($user, $role);
                    }
                }
            }

            return $response->withHeader('Location', '/admin/users?success=updated')->withStatus(302);

        } catch (\Exception $e) {
            error_log("User update error: " . $e->getMessage());
            $user = $this->entityManager->getRepository(User::class)->find($id);
            return $this->showEditFormWithErrors($user, ['An error occurred while updating the user.']);
        }
    }

    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int)$args['id'];

        // Yetki kontrolü
        if (!$this->authService->canAccess('user', 'delete')) {
            return $response->withStatus(403);
        }

        try {
            $user = $this->entityManager->getRepository(User::class)->find($id);

            if (!$user) {
                return $response->withStatus(404);
            }

            // Prevent deleting current user
            $currentUser = $this->authService->getCurrentUser();
            if ($currentUser && $currentUser->getId() === $user->getId()) {
                return $response->withHeader('Location', '/admin/users?error=cannot_delete_self')->withStatus(302);
            }

            $this->entityManager->remove($user);
            $this->entityManager->flush();

            return $response->withHeader('Location', '/admin/users?success=deleted')->withStatus(302);

        } catch (\Exception $e) {
            error_log("User deletion error: " . $e->getMessage());
            return $response->withHeader('Location', '/admin/users?error=delete_failed')->withStatus(302);
        }
    }

    private function showNewFormWithErrors(array $formData, array $errors): ResponseInterface
    {
        $roles = $this->entityManager->getRepository(Role::class)->findAll();

        $data = [
            'page_title' => 'Create New User',
            'roles' => $roles,
            'errors' => $errors,
            'form_data' => $formData,
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'url' => '/admin'],
                ['title' => 'Users', 'url' => '/admin/users'],
                ['title' => 'New User', 'url' => '#']
            ]
        ];

        $html = $this->smartyService->render('admin/users/new.tpl', $data);
        
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    private function showEditFormWithErrors(User $user, array $errors): ResponseInterface
    {
        $roles = $this->entityManager->getRepository(Role::class)->findAll();

        $data = [
            'page_title' => 'Edit User: ' . $user->getName(),
            'user' => $user,
            'roles' => $roles,
            'errors' => $errors,
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'url' => '/admin'],
                ['title' => 'Users', 'url' => '/admin/users'],
                ['title' => 'Edit User', 'url' => '#']
            ]
        ];

        $html = $this->smartyService->render('admin/users/edit.tpl', $data);
        
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }
}
