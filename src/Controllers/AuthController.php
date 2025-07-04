<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;
use Turkpin\AdminKit\Services\AuthService;
use Turkpin\AdminKit\Services\SmartyService;

class AuthController
{
    private AuthService $authService;
    private SmartyService $smartyService;
    private array $config;

    public function __construct(AuthService $authService, SmartyService $smartyService, array $config)
    {
        $this->authService = $authService;
        $this->smartyService = $smartyService;
        $this->config = $config;
    }

    public function loginForm(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // Eğer zaten giriş yapmışsa dashboard'a yönlendir
        if ($this->authService->isAuthenticated()) {
            return $response
                ->withHeader('Location', $this->config['route_prefix'] ?? '/admin')
                ->withStatus(302);
        }

        $data = [
            'page_title' => 'Giriş Yap',
            'brand_name' => $this->config['brand_name'] ?? 'AdminKit',
            'error' => $request->getAttribute('error'),
            'email' => $request->getAttribute('email', ''),
        ];

        $html = $this->smartyService->render('auth/login.tpl', $data);
        $response->getBody()->write($html);

        return $response->withHeader('Content-Type', 'text/html');
    }

    public function login(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        $email = $parsedBody['email'] ?? '';
        $password = $parsedBody['password'] ?? '';

        // Validation
        $errors = [];
        if (empty($email)) {
            $errors[] = 'E-posta adresi gereklidir.';
        }
        if (empty($password)) {
            $errors[] = 'Şifre gereklidir.';
        }

        if (!empty($errors)) {
            return $this->loginFormWithError($request, $response, implode(' ', $errors), $email);
        }

        // Email format validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->loginFormWithError($request, $response, 'Geçerli bir e-posta adresi girin.', $email);
        }

        // Authentication attempt
        try {
            if ($this->authService->authenticate($email, $password)) {
                // Başarılı giriş - dashboard'a yönlendir
                $redirectUrl = $this->config['route_prefix'] ?? '/admin';
                
                // Remember me özelliği
                if (!empty($parsedBody['remember-me'])) {
                    $this->setRememberMeCookie($response);
                }

                return $response
                    ->withHeader('Location', $redirectUrl)
                    ->withStatus(302);
            } else {
                return $this->loginFormWithError($request, $response, 'E-posta adresi veya şifre hatalı.', $email);
            }
        } catch (\Exception $e) {
            error_log('Login error: ' . $e->getMessage());
            return $this->loginFormWithError($request, $response, 'Giriş sırasında bir hata oluştu. Lütfen tekrar deneyin.', $email);
        }
    }

    public function logout(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $this->authService->logout();
            
            // Remember me cookie'sini sil
            $this->clearRememberMeCookie($response);
            
            // Login sayfasına yönlendir
            $loginUrl = ($this->config['route_prefix'] ?? '/admin') . '/login';
            
            return $response
                ->withHeader('Location', $loginUrl)
                ->withStatus(302);
        } catch (\Exception $e) {
            error_log('Logout error: ' . $e->getMessage());
            
            // Hata olsa bile login sayfasına yönlendir
            $loginUrl = ($this->config['route_prefix'] ?? '/admin') . '/login';
            return $response
                ->withHeader('Location', $loginUrl)
                ->withStatus(302);
        }
    }

    public function register(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // Kayıt özelliği aktif değilse 404 döndür
        if (!($this->config['registration_enabled'] ?? false)) {
            return $response->withStatus(404);
        }

        $parsedBody = $request->getParsedBody();
        
        if ($request->getMethod() === 'GET') {
            return $this->registerForm($request, $response);
        }

        // POST - Kayıt işlemi
        $name = trim($parsedBody['name'] ?? '');
        $email = trim($parsedBody['email'] ?? '');
        $password = $parsedBody['password'] ?? '';
        $passwordConfirm = $parsedBody['password_confirm'] ?? '';

        // Validation
        $errors = $this->validateRegistration($name, $email, $password, $passwordConfirm);
        
        if (!empty($errors)) {
            return $this->registerFormWithErrors($request, $response, $errors, compact('name', 'email'));
        }

        try {
            // Kullanıcıyı oluştur
            $user = $this->authService->createUser([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'isActive' => true
            ]);

            // Otomatik giriş yap
            $this->authService->setCurrentUser($user);

            // Dashboard'a yönlendir
            $redirectUrl = $this->config['route_prefix'] ?? '/admin';
            return $response
                ->withHeader('Location', $redirectUrl)
                ->withStatus(302);

        } catch (\Exception $e) {
            error_log('Registration error: ' . $e->getMessage());
            
            $errorMessage = 'Kayıt sırasında bir hata oluştu.';
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $errorMessage = 'Bu e-posta adresi zaten kullanılıyor.';
            }
            
            return $this->registerFormWithErrors($request, $response, [$errorMessage], compact('name', 'email'));
        }
    }

    private function registerForm(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = [
            'page_title' => 'Kayıt Ol',
            'brand_name' => $this->config['brand_name'] ?? 'AdminKit',
        ];

        $html = $this->smartyService->render('auth/register.tpl', $data);
        $response->getBody()->write($html);

        return $response->withHeader('Content-Type', 'text/html');
    }

    private function loginFormWithError(ServerRequestInterface $request, ResponseInterface $response, string $error, string $email = ''): ResponseInterface
    {
        $data = [
            'page_title' => 'Giriş Yap',
            'brand_name' => $this->config['brand_name'] ?? 'AdminKit',
            'error' => $error,
            'email' => $email,
        ];

        $html = $this->smartyService->render('auth/login.tpl', $data);
        $response->getBody()->write($html);

        return $response->withHeader('Content-Type', 'text/html');
    }

    private function registerFormWithErrors(ServerRequestInterface $request, ResponseInterface $response, array $errors, array $data = []): ResponseInterface
    {
        $templateData = [
            'page_title' => 'Kayıt Ol',
            'brand_name' => $this->config['brand_name'] ?? 'AdminKit',
            'errors' => $errors,
            'name' => $data['name'] ?? '',
            'email' => $data['email'] ?? '',
        ];

        $html = $this->smartyService->render('auth/register.tpl', $templateData);
        $response->getBody()->write($html);

        return $response->withHeader('Content-Type', 'text/html');
    }

    private function validateRegistration(string $name, string $email, string $password, string $passwordConfirm): array
    {
        $errors = [];

        if (empty($name)) {
            $errors[] = 'Ad soyad gereklidir.';
        } elseif (strlen($name) < 2) {
            $errors[] = 'Ad soyad en az 2 karakter olmalıdır.';
        }

        if (empty($email)) {
            $errors[] = 'E-posta adresi gereklidir.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Geçerli bir e-posta adresi girin.';
        }

        if (empty($password)) {
            $errors[] = 'Şifre gereklidir.';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Şifre en az 6 karakter olmalıdır.';
        }

        if ($password !== $passwordConfirm) {
            $errors[] = 'Şifreler eşleşmiyor.';
        }

        return $errors;
    }

    private function setRememberMeCookie(ResponseInterface $response): void
    {
        // Remember me token oluştur ve cookie'ye kaydet
        $token = bin2hex(random_bytes(32));
        $expires = time() + (30 * 24 * 60 * 60); // 30 gün
        
        // Cookie'yi response'a ekle
        $cookieValue = urlencode($token);
        $response = $response->withHeader('Set-Cookie', 
            "adminkit_remember={$cookieValue}; Path=/; HttpOnly; SameSite=Strict; Expires=" . gmdate('D, d M Y H:i:s T', $expires)
        );
    }

    private function clearRememberMeCookie(ResponseInterface $response): void
    {
        // Cookie'yi sil
        $response = $response->withHeader('Set-Cookie', 
            'adminkit_remember=; Path=/; HttpOnly; SameSite=Strict; Expires=' . gmdate('D, d M Y H:i:s T', time() - 3600)
        );
    }
}
