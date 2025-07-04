<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Services;

class LocalizationService
{
    private array $translations = [];
    private string $currentLocale = 'tr';
    private string $fallbackLocale = 'en';
    private array $supportedLocales = ['tr', 'en', 'de', 'fr', 'es'];
    private string $translationsPath;

    public function __construct(string $translationsPath = null)
    {
        $this->translationsPath = $translationsPath ?: __DIR__ . '/../Translations/';
        $this->loadTranslations();
    }

    /**
     * Set current locale
     */
    public function setLocale(string $locale): self
    {
        if (in_array($locale, $this->supportedLocales)) {
            $this->currentLocale = $locale;
            $this->loadTranslations();
        }
        return $this;
    }

    /**
     * Get current locale
     */
    public function getLocale(): string
    {
        return $this->currentLocale;
    }

    /**
     * Get supported locales
     */
    public function getSupportedLocales(): array
    {
        return $this->supportedLocales;
    }

    /**
     * Add supported locale
     */
    public function addSupportedLocale(string $locale): self
    {
        if (!in_array($locale, $this->supportedLocales)) {
            $this->supportedLocales[] = $locale;
        }
        return $this;
    }

    /**
     * Translate text
     */
    public function translate(string $key, array $params = [], string $locale = null): string
    {
        $locale = $locale ?: $this->currentLocale;
        
        // Try current locale
        if (isset($this->translations[$locale][$key])) {
            $translation = $this->translations[$locale][$key];
        }
        // Try fallback locale
        elseif (isset($this->translations[$this->fallbackLocale][$key])) {
            $translation = $this->translations[$this->fallbackLocale][$key];
        }
        // Return key if not found
        else {
            $translation = $key;
        }

        // Replace parameters
        foreach ($params as $param => $value) {
            $translation = str_replace(':' . $param, $value, $translation);
        }

        return $translation;
    }

    /**
     * Shorthand translate method
     */
    public function t(string $key, array $params = [], string $locale = null): string
    {
        return $this->translate($key, $params, $locale);
    }

    /**
     * Translate plural forms
     */
    public function translatePlural(string $key, int $count, array $params = [], string $locale = null): string
    {
        $locale = $locale ?: $this->currentLocale;
        
        // Get plural key based on count
        $pluralKey = $this->getPluralKey($key, $count, $locale);
        
        // Add count to parameters
        $params['count'] = $count;
        
        return $this->translate($pluralKey, $params, $locale);
    }

    /**
     * Get plural key based on locale rules
     */
    protected function getPluralKey(string $key, int $count, string $locale): string
    {
        switch ($locale) {
            case 'tr':
                // Turkish has no plural distinction for most cases
                return $key;
                
            case 'en':
                return $count === 1 ? $key : $key . '_plural';
                
            case 'de':
            case 'fr':
            case 'es':
                return $count === 1 ? $key : $key . '_plural';
                
            default:
                return $count === 1 ? $key : $key . '_plural';
        }
    }

    /**
     * Load translations for current locale
     */
    protected function loadTranslations(): void
    {
        // Load current locale
        $this->loadLocaleTranslations($this->currentLocale);
        
        // Load fallback locale if different
        if ($this->currentLocale !== $this->fallbackLocale) {
            $this->loadLocaleTranslations($this->fallbackLocale);
        }
    }

    /**
     * Load translations for specific locale
     */
    protected function loadLocaleTranslations(string $locale): void
    {
        if (!isset($this->translations[$locale])) {
            $this->translations[$locale] = [];
        }

        // Load from PHP files
        $phpFile = $this->translationsPath . $locale . '.php';
        if (file_exists($phpFile)) {
            $translations = include $phpFile;
            if (is_array($translations)) {
                $this->translations[$locale] = array_merge(
                    $this->translations[$locale],
                    $translations
                );
            }
        }

        // Load from JSON files
        $jsonFile = $this->translationsPath . $locale . '.json';
        if (file_exists($jsonFile)) {
            $json = file_get_contents($jsonFile);
            $translations = json_decode($json, true);
            if (is_array($translations)) {
                $this->translations[$locale] = array_merge(
                    $this->translations[$locale],
                    $translations
                );
            }
        }

        // Load default translations if empty
        if (empty($this->translations[$locale])) {
            $this->translations[$locale] = $this->getDefaultTranslations($locale);
        }
    }

    /**
     * Get default translations for locale
     */
    protected function getDefaultTranslations(string $locale): array
    {
        switch ($locale) {
            case 'tr':
                return [
                    // Common
                    'save' => 'Kaydet',
                    'cancel' => 'İptal',
                    'delete' => 'Sil',
                    'edit' => 'Düzenle',
                    'view' => 'Görüntüle',
                    'create' => 'Oluştur',
                    'update' => 'Güncelle',
                    'search' => 'Ara',
                    'filter' => 'Filtrele',
                    'clear' => 'Temizle',
                    'yes' => 'Evet',
                    'no' => 'Hayır',
                    'loading' => 'Yükleniyor...',
                    'error' => 'Hata',
                    'success' => 'Başarılı',
                    'warning' => 'Uyarı',
                    'info' => 'Bilgi',
                    
                    // Navigation
                    'dashboard' => 'Dashboard',
                    'home' => 'Ana Sayfa',
                    'back' => 'Geri',
                    'next' => 'İleri',
                    'previous' => 'Önceki',
                    
                    // Forms
                    'required_field' => 'Bu alan zorunludur',
                    'invalid_email' => 'Geçerli bir e-posta adresi giriniz',
                    'invalid_number' => 'Geçerli bir sayı giriniz',
                    'invalid_date' => 'Geçerli bir tarih giriniz',
                    'password_too_short' => 'Şifre çok kısa',
                    'passwords_dont_match' => 'Şifreler eşleşmiyor',
                    
                    // Table
                    'no_data' => 'Gösterilecek veri bulunamadı',
                    'total_records' => 'Toplam :count kayıt',
                    'page_of' => 'Sayfa :current / :total',
                    'records_selected' => ':count kayıt seçildi',
                    
                    // Actions
                    'confirm_delete' => 'Bu kaydı silmek istediğinizden emin misiniz?',
                    'bulk_delete' => 'Seçili kayıtları sil',
                    'export_selected' => 'Seçilenleri dışa aktar',
                    'import_data' => 'Veri içe aktar',
                    
                    // File Upload
                    'file_upload' => 'Dosya yükle',
                    'drag_drop_file' => 'Dosyayı buraya sürükleyip bırakın',
                    'file_too_large' => 'Dosya çok büyük',
                    'invalid_file_type' => 'Geçersiz dosya türü',
                    
                    // Authentication
                    'login' => 'Giriş Yap',
                    'logout' => 'Çıkış Yap',
                    'username' => 'Kullanıcı Adı',
                    'password' => 'Şifre',
                    'remember_me' => 'Beni Hatırla',
                    'forgot_password' => 'Şifremi Unuttum',
                ];

            case 'en':
                return [
                    // Common
                    'save' => 'Save',
                    'cancel' => 'Cancel',
                    'delete' => 'Delete',
                    'edit' => 'Edit',
                    'view' => 'View',
                    'create' => 'Create',
                    'update' => 'Update',
                    'search' => 'Search',
                    'filter' => 'Filter',
                    'clear' => 'Clear',
                    'yes' => 'Yes',
                    'no' => 'No',
                    'loading' => 'Loading...',
                    'error' => 'Error',
                    'success' => 'Success',
                    'warning' => 'Warning',
                    'info' => 'Info',
                    
                    // Navigation
                    'dashboard' => 'Dashboard',
                    'home' => 'Home',
                    'back' => 'Back',
                    'next' => 'Next',
                    'previous' => 'Previous',
                    
                    // Forms
                    'required_field' => 'This field is required',
                    'invalid_email' => 'Please enter a valid email address',
                    'invalid_number' => 'Please enter a valid number',
                    'invalid_date' => 'Please enter a valid date',
                    'password_too_short' => 'Password is too short',
                    'passwords_dont_match' => 'Passwords do not match',
                    
                    // Table
                    'no_data' => 'No data to display',
                    'total_records' => 'Total :count records',
                    'page_of' => 'Page :current of :total',
                    'records_selected' => ':count records selected',
                    
                    // Actions
                    'confirm_delete' => 'Are you sure you want to delete this record?',
                    'bulk_delete' => 'Delete selected',
                    'export_selected' => 'Export selected',
                    'import_data' => 'Import data',
                    
                    // File Upload
                    'file_upload' => 'Upload file',
                    'drag_drop_file' => 'Drag and drop file here',
                    'file_too_large' => 'File is too large',
                    'invalid_file_type' => 'Invalid file type',
                    
                    // Authentication
                    'login' => 'Login',
                    'logout' => 'Logout',
                    'username' => 'Username',
                    'password' => 'Password',
                    'remember_me' => 'Remember Me',
                    'forgot_password' => 'Forgot Password',
                ];

            case 'de':
                return [
                    // Common
                    'save' => 'Speichern',
                    'cancel' => 'Abbrechen',
                    'delete' => 'Löschen',
                    'edit' => 'Bearbeiten',
                    'view' => 'Anzeigen',
                    'create' => 'Erstellen',
                    'update' => 'Aktualisieren',
                    'search' => 'Suchen',
                    'filter' => 'Filtern',
                    'clear' => 'Löschen',
                    'yes' => 'Ja',
                    'no' => 'Nein',
                    'loading' => 'Wird geladen...',
                    'error' => 'Fehler',
                    'success' => 'Erfolgreich',
                    'warning' => 'Warnung',
                    'info' => 'Info',
                    
                    // Navigation
                    'dashboard' => 'Dashboard',
                    'home' => 'Startseite',
                    'back' => 'Zurück',
                    'next' => 'Weiter',
                    'previous' => 'Zurück',
                    
                    // Authentication
                    'login' => 'Anmelden',
                    'logout' => 'Abmelden',
                    'username' => 'Benutzername',
                    'password' => 'Passwort',
                ];

            case 'fr':
                return [
                    // Common
                    'save' => 'Enregistrer',
                    'cancel' => 'Annuler',
                    'delete' => 'Supprimer',
                    'edit' => 'Modifier',
                    'view' => 'Voir',
                    'create' => 'Créer',
                    'update' => 'Mettre à jour',
                    'search' => 'Rechercher',
                    'filter' => 'Filtrer',
                    'clear' => 'Effacer',
                    'yes' => 'Oui',
                    'no' => 'Non',
                    'loading' => 'Chargement...',
                    'error' => 'Erreur',
                    'success' => 'Succès',
                    'warning' => 'Attention',
                    'info' => 'Info',
                    
                    // Navigation
                    'dashboard' => 'Tableau de bord',
                    'home' => 'Accueil',
                    'back' => 'Retour',
                    'next' => 'Suivant',
                    'previous' => 'Précédent',
                    
                    // Authentication
                    'login' => 'Connexion',
                    'logout' => 'Déconnexion',
                    'username' => 'Nom d\'utilisateur',
                    'password' => 'Mot de passe',
                ];

            case 'es':
                return [
                    // Common
                    'save' => 'Guardar',
                    'cancel' => 'Cancelar',
                    'delete' => 'Eliminar',
                    'edit' => 'Editar',
                    'view' => 'Ver',
                    'create' => 'Crear',
                    'update' => 'Actualizar',
                    'search' => 'Buscar',
                    'filter' => 'Filtrar',
                    'clear' => 'Limpiar',
                    'yes' => 'Sí',
                    'no' => 'No',
                    'loading' => 'Cargando...',
                    'error' => 'Error',
                    'success' => 'Éxito',
                    'warning' => 'Advertencia',
                    'info' => 'Información',
                    
                    // Navigation
                    'dashboard' => 'Panel',
                    'home' => 'Inicio',
                    'back' => 'Atrás',
                    'next' => 'Siguiente',
                    'previous' => 'Anterior',
                    
                    // Authentication
                    'login' => 'Iniciar sesión',
                    'logout' => 'Cerrar sesión',
                    'username' => 'Usuario',
                    'password' => 'Contraseña',
                ];

            default:
                return [];
        }
    }

    /**
     * Add translation
     */
    public function addTranslation(string $locale, string $key, string $value): self
    {
        if (!isset($this->translations[$locale])) {
            $this->translations[$locale] = [];
        }
        
        $this->translations[$locale][$key] = $value;
        return $this;
    }

    /**
     * Add multiple translations
     */
    public function addTranslations(string $locale, array $translations): self
    {
        if (!isset($this->translations[$locale])) {
            $this->translations[$locale] = [];
        }
        
        $this->translations[$locale] = array_merge(
            $this->translations[$locale],
            $translations
        );
        
        return $this;
    }

    /**
     * Get all translations for locale
     */
    public function getTranslations(string $locale = null): array
    {
        $locale = $locale ?: $this->currentLocale;
        return $this->translations[$locale] ?? [];
    }

    /**
     * Check if translation exists
     */
    public function hasTranslation(string $key, string $locale = null): bool
    {
        $locale = $locale ?: $this->currentLocale;
        return isset($this->translations[$locale][$key]);
    }

    /**
     * Get locale name in native language
     */
    public function getLocaleNativeName(string $locale): string
    {
        $names = [
            'tr' => 'Türkçe',
            'en' => 'English',
            'de' => 'Deutsch',
            'fr' => 'Français',
            'es' => 'Español',
        ];

        return $names[$locale] ?? $locale;
    }

    /**
     * Get locale flag emoji
     */
    public function getLocaleFlag(string $locale): string
    {
        $flags = [
            'tr' => '🇹🇷',
            'en' => '🇺🇸',
            'de' => '🇩🇪',
            'fr' => '🇫🇷',
            'es' => '🇪🇸',
        ];

        return $flags[$locale] ?? '🌍';
    }

    /**
     * Detect locale from browser
     */
    public function detectLocale(): string
    {
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
            $languages = explode(',', $acceptLanguage);
            
            foreach ($languages as $language) {
                $locale = substr(trim($language), 0, 2);
                if (in_array($locale, $this->supportedLocales)) {
                    return $locale;
                }
            }
        }
        
        return $this->fallbackLocale;
    }

    /**
     * Set locale from session/cookie
     */
    public function setLocaleFromSession(): self
    {
        // Check session first
        if (isset($_SESSION['locale']) && in_array($_SESSION['locale'], $this->supportedLocales)) {
            $this->setLocale($_SESSION['locale']);
        }
        // Check cookie
        elseif (isset($_COOKIE['locale']) && in_array($_COOKIE['locale'], $this->supportedLocales)) {
            $this->setLocale($_COOKIE['locale']);
        }
        // Auto-detect from browser
        else {
            $detectedLocale = $this->detectLocale();
            $this->setLocale($detectedLocale);
        }
        
        return $this;
    }

    /**
     * Save locale to session and cookie
     */
    public function saveLocaleToSession(string $locale): self
    {
        if (in_array($locale, $this->supportedLocales)) {
            $_SESSION['locale'] = $locale;
            setcookie('locale', $locale, time() + (365 * 24 * 60 * 60), '/'); // 1 year
            $this->setLocale($locale);
        }
        
        return $this;
    }

    /**
     * Format date according to locale
     */
    public function formatDate(\DateTime $date, string $format = null): string
    {
        if (!$format) {
            $format = $this->getDefaultDateFormat();
        }
        
        return $date->format($format);
    }

    /**
     * Get default date format for locale
     */
    protected function getDefaultDateFormat(): string
    {
        switch ($this->currentLocale) {
            case 'tr':
                return 'd.m.Y';
            case 'en':
                return 'm/d/Y';
            case 'de':
                return 'd.m.Y';
            case 'fr':
                return 'd/m/Y';
            case 'es':
                return 'd/m/Y';
            default:
                return 'Y-m-d';
        }
    }

    /**
     * Format number according to locale
     */
    public function formatNumber(float $number, int $decimals = 2): string
    {
        switch ($this->currentLocale) {
            case 'tr':
                return number_format($number, $decimals, ',', '.');
            case 'en':
                return number_format($number, $decimals, '.', ',');
            case 'de':
                return number_format($number, $decimals, ',', '.');
            case 'fr':
                return number_format($number, $decimals, ',', ' ');
            case 'es':
                return number_format($number, $decimals, ',', '.');
            default:
                return number_format($number, $decimals, '.', ',');
        }
    }

    /**
     * Get direction for locale (LTR/RTL)
     */
    public function getDirection(string $locale = null): string
    {
        $locale = $locale ?: $this->currentLocale;
        
        $rtlLocales = ['ar', 'he', 'fa', 'ur'];
        return in_array($locale, $rtlLocales) ? 'rtl' : 'ltr';
    }
}
