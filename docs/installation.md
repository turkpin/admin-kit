# AdminKit Kurulum Rehberi

Bu rehber AdminKit'i sisteminize kurmanız için gereken tüm adımları içerir.

## Sistem Gereksinimleri

### Minimum Gereksinimler
- **PHP**: 8.1 veya üzeri
- **MySQL**: 5.7+ veya **PostgreSQL**: 12+
- **Composer**: 2.0+
- **Web Sunucusu**: Apache 2.4+ veya Nginx 1.18+
- **Bellek**: Minimum 256MB (512MB önerilen)
- **Disk Alanı**: 50MB (cache ve uploads için ek alan)

### Önerilen PHP Uzantıları
```bash
# Zorunlu uzantılar
php-pdo
php-pdo-mysql (veya php-pdo-pgsql)
php-mbstring
php-openssl
php-tokenizer
php-xml
php-ctype
php-json

# Önerilen uzantılar
php-redis      # Cache performansı için
php-gd         # Resim işlemleri için
php-curl       # API çağrıları için
php-zip        # Asset bundling için
php-intl       # Çok dil desteği için
```

### Sunucu Konfigürasyonu

#### Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Security headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
```

#### Nginx
```nginx
server {
    listen 80;
    server_name admin.example.com;
    root /path/to/adminkit/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Security
    add_header X-Content-Type-Options nosniff;
    add_header X-Frame-Options DENY;
    add_header X-XSS-Protection "1; mode=block";
}
```

## Kurulum Adımları

### 1. Composer ile Kurulum

```bash
# Yeni proje oluştur
composer create-project turkpin/admin-kit my-admin-panel

# Veya mevcut projeye ekle
composer require turkpin/admin-kit
```

### 2. Proje Yapısını Oluştur

```bash
cd my-admin-panel

# Gerekli klasörleri oluştur
mkdir -p public/uploads
mkdir -p cache
mkdir -p logs
mkdir -p config

# İzinleri ayarla
chmod 755 public/uploads
chmod 755 cache
chmod 755 logs
```

### 3. Veritabanı Kurulumu

#### MySQL
```sql
CREATE DATABASE adminkit_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'adminkit_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON adminkit_db.* TO 'adminkit_user'@'localhost';
FLUSH PRIVILEGES;
```

#### PostgreSQL
```sql
CREATE DATABASE adminkit_db;
CREATE USER adminkit_user WITH PASSWORD 'secure_password';
GRANT ALL PRIVILEGES ON DATABASE adminkit_db TO adminkit_user;
```

### 4. Konfigürasyon Dosyası

`config/app.php` dosyasını oluşturun:

```php
<?php
return [
    'app_name' => 'AdminKit Panel',
    'app_url' => 'https://admin.example.com',
    'timezone' => 'Europe/Istanbul',
    'locale' => 'tr',
    'debug' => false, // Production'da false yapın
    
    'database' => [
        'driver' => 'mysql', // mysql veya pgsql
        'host' => 'localhost',
        'port' => 3306,
        'database' => 'adminkit_db',
        'username' => 'adminkit_user',
        'password' => 'secure_password',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    ],
    
    'auth' => [
        'enabled' => true,
        'session_timeout' => 7200, // 2 saat
        '2fa_enabled' => true,
        'password_min_length' => 8,
        'max_login_attempts' => 5,
        'lockout_duration' => 900 // 15 dakika
    ],
    
    'cache' => [
        'enabled' => true,
        'driver' => 'file', // file, redis, memcached
        'ttl' => 3600,
        'prefix' => 'adminkit_'
    ],
    
    'uploads' => [
        'path' => 'public/uploads',
        'max_size' => '10MB',
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'docx', 'xlsx']
    ],
    
    'security' => [
        'csrf_protection' => true,
        'rate_limiting' => true,
        'max_requests_per_minute' => 60
    ]
];
```

### 5. Environment Dosyası (.env)

Hassas bilgiler için `.env` dosyası oluşturun:

```env
# Database
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=adminkit_db
DB_USERNAME=adminkit_user
DB_PASSWORD=secure_password

# Security
APP_KEY=your-32-character-secret-key
JWT_SECRET=your-jwt-secret-key

# Email (opsiyonel)
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=noreply@example.com
MAIL_PASSWORD=app_password
MAIL_ENCRYPTION=tls

# Redis (opsiyonel)
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_DATABASE=0
```

### 6. Veritabanı Migration

```bash
# Migration dosyalarını çalıştır
php vendor/bin/adminkit migrate

# Başlangıç verilerini yükle
php vendor/bin/adminkit seed

# Süper admin kullanıcı oluştur
php vendor/bin/adminkit create:admin
```

### 7. Public Directory Setup

`public/index.php` dosyasını oluşturun:

```php
<?php
require_once '../vendor/autoload.php';

use Turkpin\AdminKit\AdminKit;

// Konfigürasyonu yükle
$config = require '../config/app.php';

// Environment variables'ı yükle
if (file_exists('../.env')) {
    $lines = file('../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && !str_starts_with($line, '#')) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// AdminKit'i başlat
$adminKit = new AdminKit($config);

// Temel entity'leri tanımla
$adminKit->addEntity('User', [
    'table' => 'users',
    'title' => 'Kullanıcılar',
    'fields' => [
        'name' => ['type' => 'text', 'label' => 'Ad Soyad', 'required' => true],
        'email' => ['type' => 'email', 'label' => 'E-posta', 'required' => true],
        'password' => ['type' => 'password', 'label' => 'Şifre'],
        'is_active' => ['type' => 'boolean', 'label' => 'Aktif'],
        'created_at' => ['type' => 'datetime', 'label' => 'Kayıt Tarihi']
    ],
    'list_fields' => ['name', 'email', 'is_active', 'created_at'],
    'filters' => ['is_active'],
    'searchable' => ['name', 'email']
]);

// Uygulamayı çalıştır
$adminKit->run();
```

## Doğrulama ve Test

### 1. Kurulum Kontrolü
```bash
# PHP sürümünü kontrol et
php -v

# Uzantıları kontrol et
php -m | grep -E "(pdo|mbstring|openssl|xml)"

# Composer autoload'u kontrol et
php -r "require 'vendor/autoload.php'; echo 'Autoload OK\n';"
```

### 2. Veritabanı Bağlantısını Test Et
```bash
php vendor/bin/adminkit test:database
```

### 3. Web Sunucusu Testi
Tarayıcıda `http://localhost/admin` adresine gidin. Login sayfasını görmelisiniz.

### 4. İlk Giriş
```
Email: admin@example.com
Şifre: admin123
```

## Yaygın Sorunlar ve Çözümler

### 1. "Class not found" Hatası
```bash
# Composer autoload'u yenile
composer dump-autoload
```

### 2. Dosya İzin Hataları
```bash
# Gerekli izinleri ver
chmod -R 755 public/uploads
chmod -R 755 cache
chmod -R 755 logs
```

### 3. Veritabanı Bağlantı Hatası
- Database credentials'ları kontrol edin
- MySQL/PostgreSQL servisinin çalıştığından emin olun
- Firewall kurallarını kontrol edin

### 4. Session Hataları
```php
// config/app.php içinde session ayarları
'session' => [
    'driver' => 'file',
    'path' => sys_get_temp_dir(),
    'lifetime' => 120,
    'cookie_name' => 'adminkit_session'
]
```

## Production Optimizasyonları

### 1. Composer Optimizasyonu
```bash
composer install --no-dev --optimize-autoloader
```

### 2. Cache'i Aktifleştir
```php
// config/app.php
'cache' => [
    'enabled' => true,
    'driver' => 'redis', // redis production için önerilen
]
```

### 3. Debug Modunu Kapat
```php
// config/app.php
'debug' => false,
'display_errors' => false,
```

### 4. HTTPS Zorla
```php
// config/app.php
'force_https' => true,
```

## Güvenlik Kontrol Listesi

- [ ] Debug modu kapatıldı
- [ ] Güçlü şifreler kullanıldı
- [ ] HTTPS aktifleştirildi
- [ ] Firewall kuralları ayarlandı
- [ ] Backup stratejisi oluşturuldu
- [ ] Log monitoring aktifleştirildi
- [ ] 2FA etkinleştirildi
- [ ] Rate limiting aktif
- [ ] CSRF koruması aktif

## Sonraki Adımlar

Kurulum tamamlandıktan sonra:

1. **[Hızlı Başlangıç](quick-start.md)** - İlk projenizi oluşturun
2. **[Konfigürasyon](configuration.md)** - Detaylı ayarları öğrenin
3. **[Güvenlik](advanced/security.md)** - Güvenlik best practices

---

**Tebrikler!** AdminKit başarıyla kuruldu. Artık güçlü admin panellerinizi oluşturmaya başlayabilirsiniz.
