<?php

declare(strict_types=1);

namespace AdminKit\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use AdminKit\Services\SmartyService;
use AdminKit\Services\LocalizationService;

class WelcomeController
{
    private SmartyService $smartyService;
    private LocalizationService $localizationService;

    public function __construct(
        SmartyService $smartyService,
        LocalizationService $localizationService
    ) {
        $this->smartyService = $smartyService;
        $this->localizationService = $localizationService;
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // Check if this is a fresh installation
        $isFirstTime = !file_exists(ADMINKIT_ROOT . '/.env') || 
                      !file_exists(ADMINKIT_ROOT . '/config/app.php');
        
        // Get current language
        $currentLang = $this->localizationService->getLocale();
        
        // Prepare template data
        $data = [
            'page_title' => $this->localizationService->translate('welcome_to_adminkit'),
            'is_first_time' => $isFirstTime,
            'current_lang' => $currentLang,
            'supported_languages' => $this->localizationService->getSupportedLocales(),
            'features' => $this->getFeaturesList(),
            'getting_started_steps' => $this->getGettingStartedSteps(),
            'quick_actions' => $this->getQuickActions()
        ];

        // Render the welcome template
        $html = $this->smartyService->render('welcome/index.tpl', $data);
        
        $response->getBody()->write($html);
        return $response;
    }

    private function getFeaturesList(): array
    {
        return [
            [
                'icon' => 'cog',
                'title' => $this->localizationService->translate('crud_operations'),
                'description' => $this->localizationService->translate('crud_operations_desc'),
            ],
            [
                'icon' => 'shield',
                'title' => $this->localizationService->translate('authentication'),
                'description' => $this->localizationService->translate('authentication_desc'),
            ],
            [
                'icon' => 'chart-bar',
                'title' => $this->localizationService->translate('dashboard_widgets'),
                'description' => $this->localizationService->translate('dashboard_widgets_desc'),
            ],
            [
                'icon' => 'globe',
                'title' => $this->localizationService->translate('multi_language'),
                'description' => $this->localizationService->translate('multi_language_desc'),
            ],
            [
                'icon' => 'mobile',
                'title' => $this->localizationService->translate('responsive_design'),
                'description' => $this->localizationService->translate('responsive_design_desc'),
            ],
            [
                'icon' => 'puzzle',
                'title' => $this->localizationService->translate('extensible'),
                'description' => $this->localizationService->translate('extensible_desc'),
            ],
        ];
    }

    private function getGettingStartedSteps(): array
    {
        return [
            [
                'number' => 1,
                'title' => $this->localizationService->translate('install_dependencies'),
                'description' => $this->localizationService->translate('install_dependencies_desc'),
                'command' => 'composer install',
            ],
            [
                'number' => 2,
                'title' => $this->localizationService->translate('configure_database'),
                'description' => $this->localizationService->translate('configure_database_desc'),
                'command' => 'php bin/adminkit migrate',
            ],
            [
                'number' => 3,
                'title' => $this->localizationService->translate('create_admin_user'),
                'description' => $this->localizationService->translate('create_admin_user_desc'),
                'command' => 'php bin/adminkit user:create --admin',
            ],
            [
                'number' => 4,
                'title' => $this->localizationService->translate('start_developing'),
                'description' => $this->localizationService->translate('start_developing_desc'),
                'command' => 'php bin/adminkit serve',
            ],
        ];
    }

    private function getQuickActions(): array
    {
        return [
            [
                'title' => $this->localizationService->translate('view_documentation'),
                'description' => $this->localizationService->translate('view_documentation_desc'),
                'url' => 'https://github.com/oktayaydogan/admin-kit/wiki',
                'icon' => 'book',
                'color' => 'blue',
            ],
            [
                'title' => $this->localizationService->translate('explore_demo'),
                'description' => $this->localizationService->translate('explore_demo_desc'),
                'url' => '/demo',
                'icon' => 'eye',
                'color' => 'green',
            ],
            [
                'title' => $this->localizationService->translate('admin_panel'),
                'description' => $this->localizationService->translate('admin_panel_desc'),
                'url' => '/admin',
                'icon' => 'cog',
                'color' => 'purple',
            ],
            [
                'title' => $this->localizationService->translate('github_repository'),
                'description' => $this->localizationService->translate('github_repository_desc'),
                'url' => 'https://github.com/oktayaydogan/admin-kit',
                'icon' => 'code',
                'color' => 'gray',
            ],
        ];
    }
}
