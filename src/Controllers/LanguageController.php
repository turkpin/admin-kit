<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Turkpin\AdminKit\Services\LocalizationService;

class LanguageController
{
    private LocalizationService $localizationService;

    public function __construct(LocalizationService $localizationService)
    {
        $this->localizationService = $localizationService;
    }

    /**
     * Change language
     */
    public function setLanguage(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $locale = $args['locale'] ?? $request->getParsedBody()['locale'] ?? 'tr';
        
        // Validate locale
        if (!in_array($locale, $this->localizationService->getSupportedLocales())) {
            $locale = 'tr'; // fallback to Turkish
        }

        // Save to session and cookie
        $this->localizationService->saveLocaleToSession($locale);

        // Get redirect URL
        $redirectUrl = $request->getHeaderLine('Referer') ?: '/admin';
        
        return $response->withHeader('Location', $redirectUrl)->withStatus(302);
    }

    /**
     * Get current language info as JSON
     */
    public function getCurrentLanguage(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $currentLocale = $this->localizationService->getLocale();
        
        $data = [
            'current_locale' => $currentLocale,
            'current_name' => $this->localizationService->getLocaleNativeName($currentLocale),
            'current_flag' => $this->localizationService->getLocaleFlag($currentLocale),
            'supported_locales' => array_map(function($locale) {
                return [
                    'code' => $locale,
                    'name' => $this->localizationService->getLocaleNativeName($locale),
                    'flag' => $this->localizationService->getLocaleFlag($locale)
                ];
            }, $this->localizationService->getSupportedLocales())
        ];

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Get translations for JavaScript
     */
    public function getTranslations(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $locale = $request->getQueryParams()['locale'] ?? $this->localizationService->getLocale();
        
        // Get common translations for frontend
        $translations = [
            'save' => $this->localizationService->translate('save', [], $locale),
            'cancel' => $this->localizationService->translate('cancel', [], $locale),
            'delete' => $this->localizationService->translate('delete', [], $locale),
            'edit' => $this->localizationService->translate('edit', [], $locale),
            'view' => $this->localizationService->translate('view', [], $locale),
            'loading' => $this->localizationService->translate('loading', [], $locale),
            'confirm_delete' => $this->localizationService->translate('confirm_delete', [], $locale),
            'yes' => $this->localizationService->translate('yes', [], $locale),
            'no' => $this->localizationService->translate('no', [], $locale),
            'error' => $this->localizationService->translate('error', [], $locale),
            'success' => $this->localizationService->translate('success', [], $locale),
            'warning' => $this->localizationService->translate('warning', [], $locale),
            'info' => $this->localizationService->translate('info', [], $locale),
        ];

        $response->getBody()->write(json_encode($translations));
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
