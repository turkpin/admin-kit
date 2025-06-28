<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;
use Turkpin\AdminKit\Services\AuthService;

class AuthMiddleware implements MiddlewareInterface
{
    private AuthService $authService;
    private array $excludedPaths;

    public function __construct(AuthService $authService, array $excludedPaths = [])
    {
        $this->authService = $authService;
        $this->excludedPaths = array_merge([
            '/login',
            '/logout',
            '/assets',
        ], $excludedPaths);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();

        // Excluded path'leri kontrol et
        if ($this->isExcludedPath($path)) {
            return $handler->handle($request);
        }

        // Authentication kontrolü
        if (!$this->authService->isAuthenticated()) {
            return $this->redirectToLogin();
        }

        // Request'e user'ı ekle
        $user = $this->authService->getCurrentUser();
        $request = $request->withAttribute('user', $user);
        $request = $request->withAttribute('auth_service', $this->authService);

        return $handler->handle($request);
    }

    private function isExcludedPath(string $path): bool
    {
        foreach ($this->excludedPaths as $excludedPath) {
            if (str_starts_with($path, $excludedPath)) {
                return true;
            }
        }

        return false;
    }

    private function redirectToLogin(): ResponseInterface
    {
        $response = new Response();
        return $response
            ->withHeader('Location', '/admin/login')
            ->withStatus(302);
    }
}
