# AdminKit Kurulum Rehberi

Bu rehber AdminKit Composer package'ını sisteminize kurmanız için gereken tüm adımları içerir.

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
php-pdo php-pdo-mysql php-mbstring php-openssl php-tokenizer php-xml php-ctype php-json

# Önerilen uzantılar (Enterprise özellikler için)
php-redis      # Cache ve Queue performansı için
php-gd         # Resim işlemleri için
php-curl       # API çağrıları için
php-zip        # Asset bundling için
php-intl       # Gelişmiş çok dil desteği için
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

# AdminKit assets caching
<LocationMatch "\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2)$">
    ExpiresActive On
    ExpiresDefault "access plus 1 year"
</LocationMatch>
```

#### Nginx
```nginx
server {
    listen 80;
    server_name admin.example.com;
    root /path/to/project/public;
    index index.php;

    # AdminKit assets with long-term caching
    location /assets/adminkit/ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files $uri $uri/ =404;
    }

    # General asset handling
    location ~ \.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # PHP handling
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # WebSocket proxy (opsiyonel)
    location /ws {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
    }

    # Security headers
    add_header X-Content-Type-Options nosniff;
    add_header X-Frame-Options DENY;
    add_header X-XSS-Protection "1; mode=block";
}
```

## Kurulum Yöntemleri

### Yöntem 1: Otomatik Kurulum (Önerilen)

```bash
# 1. AdminKit package'ını yükleyin
composer require turkpin/admin-kit

# 2. Otomatik kurulum (config, assets, migrations)
php vendor/bin/adminkit install

# 3. Veritabanı bağlantısını yapılandırın
# config/adminkit.php dosyasını düzenleyin

# 4. Veritabanı migration'larını çalıştırın
php vendor/bin/adminkit migrate

# 5. Admin kullanıcı oluşturun
php vendor/bin/adminkit user:create

# 6. Development server'ı başlatın
php vendor/bin/adminkit serve
```

### Yöntem 2: Manuel Kurulum

#### 1. Composer Package Kurulumu

```bash
# Yeni proje oluştur
mkdir my-admin-panel
cd my-admin-panel

# AdminKit'i yükle
composer require turkpin/admin-kit

# Gerekli klasörleri oluştur
mkdir -p public config cache logs
```

#### 2. Konfigürasyon Dosyası Oluşturma

```bash
# CLI ile otomatik oluştur
php vendor/bin/adminkit publish:config

# Veya manuel olarak config/adminkit.php oluştur
```

`config/adminkit.php`:
```php
<?php
return [
    'app_name' => 'AdminKit Panel',
    'app_url' => 'http://localhost:8000',
    'timezone' => 'Europe/Istanbul',
    'locale' => 'tr',
    'debug' => true,
    
    'database' => [
        'driver' => 'mysql',
        'host' => 'localhost',
        'port' => 3306,
        'database' => 'adminkit_db',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
    ],
    
    'auth' => [
        'enabled' => true,
        'session_timeout' => 7200,
        '2fa_enabled' => true,
        'password_min_length' => 8,
        'max_login_attempts' => 5,
        'lockout_duration' => 900
    ],
    
    'cache' => [
        'enabled' => true,
        'driver' => 'file',
        'ttl' => 3600,
        'prefix' => 'adminkit_'
    ],
    
    'queue' => [
        'enabled' => true,
        'driver' => 'database',
        'table' => 'jobs',
        'max_attempts' => 3,
        'retry_delay' => 60
    ],
    
    'websocket' => [
        'enabled' => false, // Development'ta false
        'port' => 8080,
        'host' => '0.0.0.0'
    ],
    
    'performance' => [
        'enabled' => true,
        'slow_query_threshold' => 1000,
        'memory_limit_warning' => 80
    ],
    
    'uploads' => [
        'path' => 'public/uploads',
        'max_size' => '10MB',
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'docx', 'xlsx']
    ]
];
```

#### 3. Environment Dosyası (.env)

```bash
# .env dosyası oluştur
touch .env
```

`.env`:
```env
# Database
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=adminkit_db
DB_USERNAME=root
DB_PASSWORD=

# Security
APP_KEY=your-32-character-secret-key
JWT_SECRET=your-jwt-secret-key

# AdminKit Settings
ADMINKIT_DEBUG=true
ADMINKIT_LOCALE=tr
ADMINKIT_TIMEZONE=Europe/Istanbul

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

#### 4. Asset Publishing

```bash
# Asset'leri public klasörüne yayınla
php vendor/bin/adminkit publish:assets

# Template'leri yayınla
php vendor/bin/adminkit publish:templates

# Migration'ları yayınla
php vendor/bin/adminkit publish:migrations
```

#### 5. Public Directory Setup

`public/index.php`:
```php
<?php
require_once '../vendor/autoload.php';

use Turkpin\AdminKit\AdminKit;
use Turkpin\AdminKit\Providers\AdminKitServiceProvider;

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

// Konfigürasyonu yükle
$config = require '../config/adminkit.php';

// Service Provider ile AdminKit'i başlat
$container = new \DI\Container(); // veya PSR-11 uyumlu container
$provider = new AdminKitServiceProvider($container, $config);
$provider->register();
$provider->boot();

// AdminKit instance oluştur
$adminKit = $provider->createAdminKit();

// Basit entity tanımla
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

## Veritabanı Kurulumu

### MySQL Kurulumu

```bash
# MySQL'e bağlan
mysql -u root -p

# Veritabanı ve kullanıcı oluştur
CREATE DATABASE adminkit_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'adminkit_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON adminkit_db.* TO 'adminkit_user'@'localhost';
FLUSH PRIVILEGES;
```

### PostgreSQL Kurulumu

```bash
# PostgreSQL'e bağlan
sudo -u postgres psql

# Veritabanı ve kullanıcı oluştur
CREATE DATABASE adminkit_db;
CREATE USER adminkit_user WITH PASSWORD 'secure_password';
GRANT ALL PRIVILEGES ON DATABASE adminkit_db TO adminkit_user;
```

### Migration Çalıştırma

```bash
# AdminKit migration'larını çalıştır
php vendor/bin/adminkit migrate

# Seed verilerini yükle (opsiyonel)
php vendor/bin/adminkit seed

# Veritabanı durumunu kontrol et
php vendor/bin/adminkit migrate:status
```

## CLI Komutları

AdminKit zengin CLI komutları sunar:

### Kurulum ve Yönetim
```bash
php vendor/bin/adminkit install          # İlk kurulum
php vendor/bin/adminkit update           # Package güncelleme
php vendor/bin/adminkit version          # Versiyon bilgisi
```

### Asset Yönetimi
```bash
php vendor/bin/adminkit publish:assets   # Asset'leri yayınla
php vendor/bin/adminkit publish:templates # Template'leri yayınla
php vendor/bin/adminkit publish:config   # Config'i yayınla
```

### Veritabanı İşlemleri
```bash
php vendor/bin/adminkit migrate          # Migration'ları çalıştır
php vendor/bin/adminkit migrate:rollback # Son migration'ı geri al
php vendor/bin/adminkit seed             # Seed verilerini yükle
```

### Kullanıcı Yönetimi
```bash
php vendor/bin/adminkit user:create      # Admin kullanıcı oluştur
php vendor/bin/adminkit user:password    # Şifre değiştir
php vendor/bin/adminkit user:2fa         # 2FA ayarları
```

### Development Tools
```bash
php vendor/bin/adminkit serve            # Development server
php vendor/bin/adminkit queue:work       # Queue worker
php vendor/bin/adminkit websocket:start  # WebSocket server
```

## Doğrulama ve Test

### 1. Kurulum Kontrolü
```bash
# PHP sürümünü kontrol et
php -v

# Gerekli uzantıları kontrol et
php vendor/bin/adminkit check:requirements

# Konfigürasyonu test et
php vendor/bin/adminkit test:config
```

### 2. Veritabanı Bağlantısını Test Et
```bash
php vendor/bin/adminkit test:database
```

### 3. Web Sunucusu Testi
```bash
# Development server başlat
php vendor/bin/adminkit serve

# veya geleneksel yöntem
php -S localhost:8000 -t public
```

Tarayıcıda `http://localhost:8000` adresine gidin.

### 4. İlk Giriş
AdminKit varsayılan admin kullanıcısı oluşturur:
```
Email: admin@example.com
Şifre: admin123
```

## Yaygın Sorunlar ve Çözümler

### 1. Composer Autoloader Sorunu
```bash
# Autoloader'ı yenile
composer dump-autoload

# Cache'i temizle
composer clear-cache
```

### 2. Asset Publishing Sorunu
```bash
# Asset'leri zorla yeniden yayınla
php vendor/bin/adminkit publish:assets --force

# Public dizini izinlerini kontrol et
chmod -R 755 public/
```

### 3. Veritabanı Migration Hatası
```bash
# Migration durumunu kontrol et
php vendor/bin/adminkit migrate:status

# Migration'ları sıfırla ve tekrar çalıştır
php vendor/bin/adminkit migrate:reset
php vendor/bin/adminkit migrate
```

### 4. CLI Komutu Çalışmıyor
```bash
# AdminKit CLI'ın executable olduğunu kontrol et
chmod +x vendor/bin/adminkit

# PHP path'ini kontrol et
which php
```

### 5. Permission Denied Hataları
```bash
# Gerekli izinleri ver
chmod -R 755 public/assets/
chmod -R 755 cache/
chmod -R 755 logs/
chmod -R 755 uploads/
```

## Development Environment Setup

### Tam Development Kurulumu
```bash
# 1. Proje klonla
git clone https://github.com/oktayaydogan/admin-kit.git
cd admin-kit

# 2. Dependencies yükle
composer install

# 3. Development konfigürasyonu
php vendor/bin/adminkit install --dev

# 4. Development server başlat
php vendor/bin/adminkit serve --port=8000

# 5. Queue worker başlat (ayrı terminal)
php vendor/bin/adminkit queue:work

# 6. WebSocket server başlat (opsiyonel, ayrı terminal)
php vendor/bin/adminkit websocket:start
```

### Development Tools
```bash
# Code quality tools
composer run analyse      # PHPStan analysis
composer run format       # Code formatting
composer run test         # Unit tests

# AdminKit development tools
php vendor/bin/adminkit dev:cache-clear    # Dev cache temizle
php vendor/bin/adminkit dev:watch-assets   # Asset watching
```

## Production Optimizasyonları

### 1. Composer Optimizasyonu
```bash
composer install --no-dev --optimize-autoloader --classmap-authoritative
```

### 2. AdminKit Production Setup
```bash
# Production kurulum
php vendor/bin/adminkit install --production

# Cache'leri ısıt
php vendor/bin/adminkit cache:warm

# Asset'leri minify et
php vendor/bin/adminkit publish:assets --minify
```

### 3. Debug Modunu Kapat
```php
// config/adminkit.php
'debug' => false,
'display_errors' => false,
```

### 4. HTTPS ve Security Headers
```nginx
# Nginx configuration
server {
    listen 443 ssl http2;
    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;
    
    # Security headers
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Content-Type-Options nosniff;
    add_header X-Frame-Options DENY;
    add_header X-XSS-Protection "1; mode=block";
    add_header Referrer-Policy "strict-origin-when-cross-origin";
}
```

## Güvenlik Kontrol Listesi

- [ ] Debug modu production'da kapatıldı
- [ ] Güçlü database şifreleri kullanıldı
- [ ] HTTPS aktifleştirildi
- [ ] Security headers yapılandırıldı
- [ ] File upload güvenliği yapılandırıldı
- [ ] Backup stratejisi oluşturuldu
- [ ] Log monitoring aktifleştirildi
- [ ] 2FA etkinleştirildi
- [ ] Rate limiting aktif
- [ ] CSRF koruması aktif

## Sonraki Adımlar

Kurulum tamamlandıktan sonra:

1. **[Hızlı Başlangıç](quick-start.md)** - İlk projenizi oluşturun
2. **[CLI Araçları](cli-tools.md)** - Tüm CLI komutlarını öğrenin
3. **[Service Provider](service-provider.md)** - Dependency injection kullanın
4. **[Güvenlik](../advanced/security.md)** - Güvenlik best practices

---

**Tebrikler!** AdminKit başarıyla kuruldu. Artık güçlü enterprise admin panellerinizi oluşturmaya başlayabilirsiniz.

**AdminKit** - Türk geliştiriciler için optimize edilmiş, EasyAdmin'den üstün enterprise admin panel çözümü.
