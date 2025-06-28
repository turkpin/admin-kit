<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;
use Turkpin\AdminKit\Services\AuthService;

class RoleMiddleware implements MiddlewareInterface
{
    private AuthService $authService;
    private array $rolePermissions;

    public function __construct(AuthService $authService, array $rolePermissions = [])
    {
        $this->authService = $authService;
        $this->rolePermissions = $rolePermissions;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        $method = $request->getMethod();

        // Permission kontrolü yap
        if (!$this->hasPermissionForPath($path, $method)) {
            return $this->accessDenied();
        }

        return $handler->handle($request);
    }

    private function hasPermissionForPath(string $path, string $method): bool
    {
        // Public path'ler için permission kontrolü yapma
        $publicPaths = ['/admin/login', '/admin/logout', '/admin/assets'];
        
        foreach ($publicPaths as $publicPath) {
            if (str_starts_with($path, $publicPath)) {
                return true;
            }
        }

        // User authentication kontrolü
        if (!$this->authService->isAuthenticated()) {
            return false;
        }

        // Super admin her şeye erişebilir
        if ($this->authService->hasRole('super_admin')) {
            return true;
        }

        // Path'e göre resource ve action belirle
        $pathInfo = $this->parsePathInfo($path, $method);
        if (!$pathInfo) {
            return true; // Tanımlanmamış path'ler için izin ver
        }

        $resource = $pathInfo['resource'];
        $action = $pathInfo['action'];

        return $this->authService->canAccess($resource, $action);
    }

    private function parsePathInfo(string $path, string $method): ?array
    {
        // /admin prefix'ini kaldır
        $path = preg_replace('#^/admin/?#', '', $path);
        
        if (empty($path)) {
            return ['resource' => 'dashboard', 'action' => 'index'];
        }

        $segments = explode('/', trim($path, '/'));
        
        if (empty($segments[0])) {
            return ['resource' => 'dashboard', 'action' => 'index'];
        }

        $resource = $segments[0];
        
        // Action belirleme
        $action = 'index'; // Default action
        
        if (count($segments) === 1) {
            // /admin/users -> users.index
            $action = strtolower($method) === 'post' ? 'create' : 'index';
        } elseif (count($segments) === 2) {
            if ($segments[1] === 'new') {
                // /admin/users/new -> users.new
                $action = 'new';
            } elseif (is_numeric($segments[1])) {
                // /admin/users/123 -> users.show
                $action = strtolower($method) === 'post' ? 'update' : 'show';
            }
        } elseif (count($segments) === 3) {
            if (is_numeric($segments[1]) && $segments[2] === 'edit') {
                // /admin/users/123/edit -> users.edit
                $action = 'edit';
            }
        }

        // HTTP method'a göre action düzeltmeleri
        switch (strtolower($method)) {
            case 'post':
                if ($action === 'index') $action = 'create';
                break;
            case 'put':
            case 'patch':
                $action = 'update';
                break;
            case 'delete':
                $action = 'delete';
                break;
        }

        return [
            'resource' => $resource,
            'action' => $action
        ];
    }

    private function accessDenied(): ResponseInterface
    {
        $response = new Response();
        $response->getBody()->write('Access Denied');
        
        return $response
            ->withStatus(403)
            ->withHeader('Content-Type', 'text/html');
    }

    public function setRolePermissions(array $rolePermissions): void
    {
        $this->rolePermissions = $rolePermissions;
    }

    public function addRolePermission(string $role, array $permissions): void
    {
        $this->rolePermissions[$role] = array_merge(
            $this->rolePermissions[$role] ?? [],
            $permissions
        );
    }
}
