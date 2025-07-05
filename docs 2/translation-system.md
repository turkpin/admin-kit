# AdminKit Translation System v1.0.6

AdminKit v1.0.6 introduces a comprehensive internationalization (i18n) system that provides complete multi-language support for admin panels.

## 🌍 Overview

The translation system is designed to:
- **Eliminate Hard-coded Strings**: All UI text is translatable
- **Performance Optimized**: Static caching with graceful fallbacks
- **Developer Friendly**: Simple API with parameter substitution
- **Frontend Ready**: JavaScript integration included
- **Extensible**: Easy to add new languages

## 🚀 Quick Start

### Basic Template Usage

```smarty
{* Simple translation *}
<h1>{adminkit_translate('welcome')}</h1>

{* Translation with parameters *}
<p>{adminkit_translate('user_count', ['count' => 42])}</p>

{* Using variables *}
<span>{adminkit_translate('hello_user', ['name' => $user->getName()])}</span>
```

### JavaScript Integration

```php
// In your PHP controller/template
echo '<script>const translations = ' . adminkit_translate_js() . ';</script>';
```

```javascript
// In your JavaScript
console.log(translations.welcome); // "Hoş Geldiniz!" or "Welcome!"
alert(translations.confirm_delete); // Localized confirmation message
```

## 📁 File Structure

```
src/Translations/
├── tr.php          # Turkish translations (400+ keys)
├── en.php          # English translations (400+ keys)
└── [locale].php    # Additional languages
```

### Translation File Format

```php
<?php
// src/Translations/tr.php
return [
    'welcome' => 'Hoş Geldiniz',
    'user_count' => ':count kullanıcı',
    'hello_user' => 'Merhaba, :name!',
    
    // Organized by categories
    'auth' => [
        'login' => 'Giriş Yap',
        'logout' => 'Çıkış Yap',
        // ...
    ],
    
    // Or flat structure (preferred)
    'auth_login' => 'Giriş Yap',
    'auth_logout' => 'Çıkış Yap',
];
```

## 🔧 API Reference

### Helper Functions

#### `adminkit_translate($key, $parameters = [], $locale = null)`

Translates a key with optional parameters.

```php
// Basic usage
$text = adminkit_translate('welcome');

// With parameters
$text = adminkit_translate('user_count', ['count' => 5]);

// Specific locale
$text = adminkit_translate('welcome', [], 'en');
```

#### `adminkit_translate_js($locale = null)`

Returns JavaScript-ready translation object.

```php
// Get translations for current locale
$jsTranslations = adminkit_translate_js();

// Get translations for specific locale
$jsTranslations = adminkit_translate_js('en');

// Output in template
echo '<script>window.translations = ' . adminkit_translate_js() . ';</script>';
```

### Template Functions

#### `{adminkit_translate}` Smarty Function

```smarty
{* Basic translation *}
{adminkit_translate('key')}

{* With parameters *}
{adminkit_translate('key', ['param1' => 'value1', 'param2' => 'value2'])}

{* With variables *}
{adminkit_translate('welcome_user', ['name' => $user.name])}
```

## 🎯 Parameter Substitution

The translation system supports dynamic parameter replacement:

### Simple Parameters

```php
// Translation file
'welcome_user' => 'Welcome, :name!'

// Usage
adminkit_translate('welcome_user', ['name' => 'John'])
// Output: "Welcome, John!"
```

### Multiple Parameters

```php
// Translation file
'pagination_info' => 'Showing :start to :end of :total results'

// Usage
adminkit_translate('pagination_info', [
    'start' => 1,
    'end' => 10,
    'total' => 100
])
// Output: "Showing 1 to 10 of 100 results"
```

### Complex Parameters

```php
// Translation file
'user_profile' => ':name (:email) - Last login: :last_login'

// Usage
adminkit_translate('user_profile', [
    'name' => $user->getName(),
    'email' => $user->getEmail(),
    'last_login' => $user->getLastLogin()->format('Y-m-d H:i')
])
```

## 🏗️ Architecture

### Caching System

The translation system uses static caching for optimal performance:

```php
// First call loads and caches translations
$text1 = adminkit_translate('welcome'); // Loads tr.php

// Subsequent calls use cached data
$text2 = adminkit_translate('goodbye'); // Uses cache
```

### Fallback Mechanism

When a translation is missing:

1. **Key Returned**: The original key is returned as fallback
2. **No Errors**: Silent fallback prevents breaking the UI
3. **Development Aid**: Missing keys are easily identified

```php
// If 'missing_key' doesn't exist in translation file
adminkit_translate('missing_key'); // Returns: "missing_key"
```

### Locale Resolution

The system determines locale in this order:

1. **Explicit Parameter**: `adminkit_translate('key', [], 'en')`
2. **Environment Variable**: `APP_LOCALE` from `.env`
3. **Default Fallback**: `'tr'` (Turkish)

## 🌐 Supported Languages

### Turkish (tr) - Native Language
- **Coverage**: 400+ translation keys
- **Categories**: Authentication, CRUD, Dashboard, Forms, Validation
- **Status**: ✅ Complete

### English (en) - International Language  
- **Coverage**: 400+ translation keys
- **Categories**: Authentication, CRUD, Dashboard, Forms, Validation
- **Status**: ✅ Complete

## 📝 Translation Categories

### Authentication & Security
```php
'login' => 'Giriş Yap',
'logout' => 'Çıkış Yap',
'two_factor_auth' => 'İki Faktörlü Kimlik Doğrulama',
'invalid_credentials' => 'Geçersiz kimlik bilgileri',
```

### CRUD Operations
```php
'save' => 'Kaydet',
'edit' => 'Düzenle',
'delete' => 'Sil',
'create' => 'Oluştur',
'record_created_success' => 'Kayıt başarıyla oluşturuldu',
```

### Dashboard & Navigation
```php
'dashboard' => 'Dashboard',
'welcome' => 'Hoş Geldiniz',
'quick_actions' => 'Hızlı İşlemler',
'system_info' => 'Sistem Bilgileri',
```

### Form Validation
```php
'required_field' => 'Bu alan zorunludur',
'invalid_email' => 'Geçerli bir e-posta adresi giriniz',
'password_too_short' => 'Şifre çok kısa (minimum 6 karakter)',
```

### Table & Pagination
```php
'no_records_found' => 'Henüz kayıt bulunmuyor',
'pagination_previous' => 'Önceki',
'pagination_next' => 'Sonraki',
'total_records' => 'Toplam :count kayıt',
```

## 🔄 Adding New Languages

### 1. Create Translation File

```php
// src/Translations/de.php (German example)
<?php

return [
    'welcome' => 'Willkommen',
    'login' => 'Anmelden',
    'logout' => 'Abmelden',
    // ... copy all keys from tr.php or en.php and translate
];
```

### 2. Update Helper Function (Optional)

For automatic locale detection, you might want to update the helper:

```php
// In src/helpers.php, modify adminkit_translate() function
$locale = $locale ?? $_ENV['APP_LOCALE'] ?? 'tr';
```

### 3. Test New Language

```php
// Test in controller or template
$germanText = adminkit_translate('welcome', [], 'de');
echo $germanText; // "Willkommen"
```

## 🎨 Template Integration Examples

### Login Form

```smarty
<form method="post">
    <h2>{adminkit_translate('login_to_admin_panel')}</h2>
    
    <div class="form-group">
        <label>{adminkit_translate('email')}</label>
        <input type="email" name="email" required>
    </div>
    
    <div class="form-group">
        <label>{adminkit_translate('password')}</label>
        <input type="password" name="password" required>
    </div>
    
    <button type="submit">{adminkit_translate('login')}</button>
    
    {if $error}
    <div class="error">
        {adminkit_translate('login_failed')}
    </div>
    {/if}
</form>
```

### Data Table

```smarty
<table>
    <thead>
        <tr>
            <th>{adminkit_translate('name')}</th>
            <th>{adminkit_translate('email')}</th>
            <th>{adminkit_translate('created_at')}</th>
            <th>{adminkit_translate('actions')}</th>
        </tr>
    </thead>
    <tbody>
        {foreach $users as $user}
        <tr>
            <td>{$user.name}</td>
            <td>{$user.email}</td>
            <td>{$user.created_at|date_format:'d.m.Y'}</td>
            <td>
                <a href="/users/{$user.id}/edit">{adminkit_translate('edit')}</a>
                <button onclick="deleteUser({$user.id})">{adminkit_translate('delete')}</button>
            </td>
        </tr>
        {foreachelse}
        <tr>
            <td colspan="4">{adminkit_translate('no_records_found')}</td>
        </tr>
        {/foreach}
    </tbody>
</table>

{if $pagination.total_pages > 1}
<div class="pagination">
    <span>{adminkit_translate('pagination_range', [
        'start' => $pagination.start,
        'end' => $pagination.end,
        'total' => $pagination.total
    ])}</span>
</div>
{/if}
```

### JavaScript Integration

```smarty
<script>
// Include translations in page
window.AdminKit = {
    translations: {adminkit_translate_js()|raw}
};

// Usage in JavaScript
function confirmDelete() {
    return confirm(AdminKit.translations.confirm_delete_single);
}

function showNotification(type, message) {
    const notifications = {
        success: AdminKit.translations.success,
        error: AdminKit.translations.error,
        warning: AdminKit.translations.warning
    };
    
    alert(notifications[type] + ': ' + message);
}
</script>
```

## ⚡ Performance Considerations

### Static Caching
- Translation files are loaded once per request
- Cached in static variables for subsequent calls
- No database queries required

### Memory Usage
- ~400 keys ≈ 50KB memory per language
- Negligible impact on application performance
- Only active locale is loaded

### Best Practices

1. **Load Once**: Include JavaScript translations once per page
2. **Cache Results**: Store frequently used translations in variables
3. **Lazy Loading**: Only load translations when needed
4. **Optimize Keys**: Use descriptive but concise translation keys

## 🔍 Debugging & Development

### Missing Translation Detection

```php
// In development, log missing translations
function adminkit_translate($key, $parameters = [], $locale = null) {
    // ... existing code ...
    
    if (!isset($translations[$locale][$key])) {
        if ($_ENV['APP_DEBUG'] ?? false) {
            error_log("Missing translation: {$key} for locale: {$locale}");
        }
        return $key; // Fallback to key
    }
    
    // ... rest of function
}
```

### Translation Coverage

Check translation coverage by comparing key counts:

```php
$trTranslations = require 'src/Translations/tr.php';
$enTranslations = require 'src/Translations/en.php';

$trKeys = array_keys($trTranslations);
$enKeys = array_keys($enTranslations);

$missingInEn = array_diff($trKeys, $enKeys);
$missingInTr = array_diff($enKeys, $trKeys);

echo "Missing in EN: " . count($missingInEn) . "\n";
echo "Missing in TR: " . count($missingInTr) . "\n";
```

## 🤝 Contributing Translations

We welcome contributions for new languages and improvements to existing translations.

### Translation Guidelines

1. **Consistency**: Use consistent terminology throughout
2. **Context**: Consider the UI context when translating
3. **Length**: Keep translations reasonably similar in length
4. **Formality**: Match the formality level of the application
5. **Testing**: Test translations in actual UI context

### Contribution Process

1. Fork the repository
2. Create translation file for your language
3. Test thoroughly in UI
4. Submit pull request with examples
5. Include native speaker review if possible

---

**Need help with translations?** Check our [GitHub discussions](https://github.com/turkpin/admin-kit/discussions) or [open an issue](https://github.com/turkpin/admin-kit/issues).
