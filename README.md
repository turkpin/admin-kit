# AdminKit

AdminKit, PHP tabanlı modern ve kurumsal admin panel çözümüdür. Symfony EasyAdmin alternatifi olarak geliştirilmiş, Türk geliştiriciler için optimize edilmiştir.

## Neden AdminKit?

AdminKit, EasyAdmin'in tüm özelliklerini içerirken aşağıdaki alanlarda üstünlük sağlar:

- **Türkçe Desteği**: Tam Türkçe dil desteği ve lokalizasyon (600+ çeviri)
- **Kurumsal Güvenlik**: İki faktörlü kimlik doğrulama (2FA) ve gelişmiş denetim sistemi
- **Modern Teknoloji**: PHP 8+ ve Tailwind CSS ile modern kod yapısı
- **Performans**: Gelişmiş önbellekleme ve performans izleme araçları
- **Esneklik**: Plugin mimarisi ve hook sistemi ile genişletilebilirlik
- **Gerçek Zamanlı**: WebSocket desteği ile canlı güncellemeler

## 📦 Hızlı Kurulum

### Composer ile Kurulum

```bash
# AdminKit package'ını yükleyin
composer require turkpin/admin-kit

# Otomatik kurulum
php vendor/bin/adminkit install

# Asset'leri yayınlayın
php vendor/bin/adminkit publish:assets
```

### CLI Kurulum Adımları

AdminKit güçlü CLI araçları ile gelir:

```bash
# 1. Package kurulumu
composer require turkpin/admin-kit

# 2. Otomatik setup (config, assets, migrations)
php vendor/bin/adminkit install

# 3. Veritabanı migration
php vendor/bin/adminkit migrate

# 4. Admin kullanıcı oluştur
php vendor/bin/adminkit user:create

# 5. Sunucuyu başlat
php -S localhost:8000 -t public
```

### Manual Kurulum

```php
<?php
// public/index.php
require_once '../vendor/autoload.php';

use Turkpin\AdminKit\AdminKit;
use Turkpin\AdminKit\Providers\AdminKitServiceProvider;

// Konfigürasyon
$config = require '../config/adminkit.php';

// Service Provider ile başlat
$container = new Container(); // PSR-11 container
$provider = new AdminKitServiceProvider($container, $config);
$provider->register();
$provider->boot();

// AdminKit instance oluştur
$adminKit = $provider->createAdminKit();

// Entity'leri tanımla
$adminKit->addEntity('User', [
    'table' => 'users',
    'fields' => [
        'name' => ['type' => 'text', 'label' => 'Ad Soyad', 'required' => true],
        'email' => ['type' => 'email', 'label' => 'E-posta', 'required' => true],
        'is_active' => ['type' => 'boolean', 'label' => 'Aktif']
    ]
]);

// Uygulamayı çalıştır
$adminKit->run();
```

## 🛠️ CLI Araçları

AdminKit zengin CLI komutları sunar:

### Kurulum ve Yönetim
```bash
# Kurulum ve güncelleme
php vendor/bin/adminkit install          # İlk kurulum
php vendor/bin/adminkit update           # Package güncelleme
php vendor/bin/adminkit version          # Versiyon bilgisi

# Asset yönetimi
php vendor/bin/adminkit publish:assets   # Asset'leri yayınla
php vendor/bin/adminkit publish:templates # Template'leri yayınla
php vendor/bin/adminkit publish:migrations # Migration'ları yayınla
```

### Veritabanı İşlemleri
```bash
# Migration ve seed
php vendor/bin/adminkit migrate          # Migration'ları çalıştır
php vendor/bin/adminkit seed             # Seed verilerini yükle
php vendor/bin/adminkit migrate:reset    # Migration'ları sıfırla
```

### Kullanıcı Yönetimi
```bash
# Kullanıcı işlemleri
php vendor/bin/adminkit user:create      # Admin kullanıcı oluştur
php vendor/bin/adminkit user:password    # Kullanıcı şifresi değiştir
php vendor/bin/adminkit user:2fa         # 2FA etkinleştir/devre dışı
```

### Sunucu İşlemleri
```bash
# Sunucu servisleri
php vendor/bin/adminkit serve            # Development server başlat
php vendor/bin/adminkit queue:work       # Queue worker başlat
php vendor/bin/adminkit websocket:start  # WebSocket server başlat
```

### Cache ve Temizlik
```bash
# Cache işlemleri
php vendor/bin/adminkit cache:clear      # Cache temizle
php vendor/bin/adminkit cache:warm       # Cache'i ısıt
php vendor/bin/adminkit cache:status     # Cache durumu

# Temizlik işlemleri
php vendor/bin/adminkit cleanup:logs     # Log dosyalarını temizle
php vendor/bin/adminkit cleanup:temp     # Temp dosyalarını temizle
```

## 📚 Dokümantasyon

AdminKit kapsamlı dokümantasyon sistemine sahiptir:

### 🚀 Başlangıç Rehberleri
- **[Kurulum Rehberi](docs/installation.md)** - Sistem gereksinimleri ve adım adım kurulum
- **[Hızlı Başlangıç](docs/quick-start.md)** - 5 dakikada çalışan blog sistemi
- **[CLI Araçları](docs/cli-tools.md)** - Tüm CLI komutları ve kullanımları

### 📖 Kullanım Kılavuzları
- **[Alan Türleri](docs/field-types.md)** - 14 farklı alan türü ve kullanımları
- **[Service Provider](docs/service-provider.md)** - Dependency injection ve servis yönetimi
- **[API Referansı](docs/api-reference.md)** - Tam API dokümantasyonu

### 🏢 Enterprise Servisler
- **[Queue Service](docs/services/queue-service.md)** - Arkaplan işleme ve zamanlanmış görevler
- **[Performance Service](docs/services/performance-service.md)** - Performans izleme ve profiling
- **[WebSocket Service](docs/services/websocket-service.md)** - Gerçek zamanlı özellikler
- **[Asset Service](docs/services/asset-service.md)** - Asset yönetimi ve build araçları
- **[Dynamic Forms](docs/services/dynamic-forms.md)** - Koşullu alanlar ve çok adımlı formlar
- **[Tüm Servisler](docs/services/)** - Enterprise servislerin tam listesi

### 🎯 Pratik Örnekler
- **[Temel CRUD](docs/tutorials/basic-crud.md)** - İlk entity'nizi oluşturun
- **[E-ticaret Setup](docs/tutorials/ecommerce-setup.md)** - Online mağaza admin paneli
- **[Blog CMS](docs/tutorials/blog-management.md)** - İçerik yönetim sistemi
- **[Package Integration](docs/tutorials/package-integration.md)** - Mevcut projeye entegrasyon

**➡️ [Tüm Dokümantasyon](docs/)** - Kapsamlı rehber ve örnekler

## ✨ Özellikler

### Package Özellikleri
- **🎯 Tek komut kurulum**: `composer require turkpin/admin-kit`
- **⚡ Otomatik setup**: CLI installer ile hızlı kurulum
- **🏗️ Service Provider**: Modern dependency injection
- **📦 Asset Management**: Otomatik asset publishing ve versioning
- **🔧 CLI Tools**: 20+ yönetim komutu
- **🌍 Multi-environment**: Development/production konfigürasyonu

### Temel Özellikler
- CRUD operasyonları ve entity yönetimi
- 14 farklı alan tipi (text, email, number, date, file, image vb.)
- Rol tabanlı erişim kontrolü (RBAC)
- Çoklu dil desteği (Türkçe/İngilizce) - 600+ çeviri
- Responsive tasarım (4 farklı tema)
- Breadcrumb navigasyon sistemi

### Enterprise Özellikler
- İki faktörlü kimlik doğrulama (TOTP, backup kodları)
- Arkaplan işleme sistemi (queue) ve zamanlanmış görevler
- Performans izleme ve profiling araçları
- Gelişmiş bildirim sistemi (5 kanal: toast, flash, alert, email, database)
- Plugin sistemi (hook/event mimarisi)
- Gelişmiş filtreleme ve arama (16 operatör, SQL önizleme)
- Toplu işlemler (batch operations)
- Veri dışa/içe aktarma (CSV, Excel, JSON, XML, PDF)
- Denetim günlüğü (audit log) ve değişiklik takibi

### Gelişmiş UI/UX Özellikleri
- **WebSocket & Real-time**: Canlı bildirimler, kullanıcı varlığı takibi
- **Asset Management**: Webpack/Vite entegrasyonu, minifikasyon, versiyonlama
- **Dynamic Forms**: Koşullu alanlar, çok adımlı formlar, otomatik kaydet
- **Breadcrumb Navigation**: Otomatik breadcrumb oluşturma ve hiyerarşik navigasyon

## 🔧 Sistem Gereksinimleri

### Minimum Gereksinimler
- **PHP**: 8.1 veya üzeri
- **MySQL**: 5.7+ veya **PostgreSQL**: 12+
- **Composer**: 2.0+
- **Web Sunucusu**: Apache/Nginx
- **Memory**: 256MB (512MB önerilen)

### Önerilen PHP Uzantıları
```bash
# Zorunlu uzantılar
php-pdo php-pdo-mysql php-mbstring php-openssl php-json

# Önerilen uzantılar (Enterprise özellikler için)
php-redis     # Cache ve Queue için
php-gd        # Resim işlemleri için
php-zip       # Asset bundling için
php-intl      # Gelişmiş i18n için
```

## 🚀 Hızlı Başlangıç Örnekleri

### 1. Basit Blog Sistemi (5 dakika)

```php
<?php
require_once 'vendor/autoload.php';

use Turkpin\AdminKit\AdminKit;

$adminKit = new AdminKit([
    'database' => [
        'driver' => 'mysql',
        'host' => 'localhost',
        'database' => 'blog_db',
        'username' => 'root',
        'password' => ''
    ]
]);

// Blog kategorileri
$adminKit->addEntity('Category', [
    'table' => 'categories',
    'fields' => [
        'name' => ['type' => 'text', 'label' => 'Kategori Adı', 'required' => true],
        'slug' => ['type' => 'text', 'label' => 'URL Slug'],
        'is_active' => ['type' => 'boolean', 'label' => 'Aktif', 'default' => true]
    ]
]);

// Blog yazıları
$adminKit->addEntity('Post', [
    'table' => 'posts',
    'fields' => [
        'title' => ['type' => 'text', 'label' => 'Başlık', 'required' => true],
        'content' => ['type' => 'textarea', 'label' => 'İçerik', 'rows' => 10],
        'category_id' => [
            'type' => 'association',
            'label' => 'Kategori',
            'target_entity' => 'Category',
            'display_field' => 'name'
        ],
        'featured_image' => ['type' => 'image', 'label' => 'Öne Çıkan Resim'],
        'published_at' => ['type' => 'datetime', 'label' => 'Yayın Tarihi']
    ]
]);

$adminKit->run();
```

### 2. Enterprise Özelliklerle

```php
// Service Provider kullanımı
use Turkpin\AdminKit\Providers\AdminKitServiceProvider;

$provider = new AdminKitServiceProvider($container, $config);
$provider->register();
$adminKit = $provider->createAdminKit();

// Enterprise servisleri kullan
$queueService = $adminKit->getQueueService();
$queueService->dispatch('email', ['to' => 'user@example.com']);

$performanceService = $adminKit->getPerformanceService();
$metrics = $performanceService->getMetrics();

$webSocketService = $adminKit->getWebSocketService();
$webSocketService->broadcast('notification', ['message' => 'Hello World!']);
```

## 🏭 Production Deployment

### Composer Scripts
```bash
# Production optimizasyonu
composer install --no-dev --optimize-autoloader

# AdminKit production setup
php vendor/bin/adminkit install --skip-dev
php vendor/bin/adminkit cache:warm
php vendor/bin/adminkit publish:assets --minify
```

### Supervisor Configuration
```ini
# /etc/supervisor/conf.d/adminkit-queue.conf
[program:adminkit-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/project/vendor/bin/adminkit queue:work
autostart=true
autorestart=true
numprocs=3
redirect_stderr=true
stdout_logfile=/var/log/adminkit-queue.log
```

### Nginx Configuration
```nginx
server {
    listen 80;
    server_name admin.example.com;
    root /path/to/project/public;
    index index.php;

    # AdminKit assets
    location /assets/adminkit/ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # WebSocket proxy
    location /ws {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
    }
}
```

## 📊 Package Bilgileri

### Composer Package
- **Package Name**: `turkpin/admin-kit`
- **Type**: `library`
- **License**: `MIT`
- **PHP Version**: `>=8.1`
- **Namespace**: `Turkpin\AdminKit`

### Version History
- **v1.0.0**: Production release with full EasyAdmin parity + 20 superior features
- **v0.9.x**: Beta releases with enterprise features
- **v0.8.x**: Alpha releases with basic functionality

### Dependencies
```json
{
    "php": ">=8.1",
    "slim/slim": "^4.0",
    "doctrine/orm": "^2.15",
    "smarty/smarty": "^4.3",
    "symfony/console": "^6.0|^7.0"
}
```

## 🌐 Internationalization

```php
// Çeviri sistemi
$localization = $adminKit->getLocalizationService();
echo $localization->get('user_created'); // "Kullanıcı başarıyla oluşturuldu."

// Parametreli çeviri
echo $localization->get('welcome_message', ['name' => 'Ahmet']);

// Dil değiştirme
$localization->setLocale('en'); // English
$localization->setLocale('tr'); // Türkçe
```

## 🔗 API Kullanımı

AdminKit otomatik REST API endpoints sağlar:

```bash
# CRUD operasyonları
GET    /api/users              # Kullanıcıları listele
GET    /api/users/{id}         # Kullanıcı detayı
POST   /api/users              # Yeni kullanıcı
PUT    /api/users/{id}         # Kullanıcı güncelle
DELETE /api/users/{id}         # Kullanıcı sil

# Filtreleme ve arama
GET /api/users?search=ahmet
GET /api/users?filter[is_active]=true
GET /api/users?sort=created_at&order=desc

# Real-time endpoints
GET /api/sse-messages          # Server-Sent Events
GET /api/websocket/info        # WebSocket bağlantı bilgileri
```

## 📞 Destek ve Topluluk

### GitHub
- **Repository**: [oktayaydogan/admin-kit](https://github.com/oktayaydogan/admin-kit)
- **Issues**: Bug reports ve feature requests
- **Discussions**: Topluluk tartışmaları
- **Wiki**: Gelişmiş kullanım örnekleri

### Dokümantasyon
- **[Resmi Dokümantasyon](docs/)** - Kapsamlı rehberler
- **[API Reference](docs/api-reference.md)** - Tam API dokümantasyonu
- **[Examples](docs/examples/)** - Gerçek dünya örnekleri

### Türkçe Topluluk
- **Türkçe Destek**: Tam Türkçe dokümantasyon
- **E-posta**: support@turkpin.com
- **Maintainer**: oktayaydogan@gmail.com

## 🤝 Katkıda Bulunma

AdminKit açık kaynak bir projedir. Katkılarınızı bekliyoruz!

```bash
# Development setup
git clone https://github.com/oktayaydogan/admin-kit.git
cd admin-kit
composer install
php vendor/bin/adminkit install --dev
```

Detaylı katkı rehberi için [CONTRIBUTING.md](CONTRIBUTING.md) dosyasını okuyun.

## 📜 Lisans

MIT License. Detaylar için [LICENSE](LICENSE) dosyasına bakın.

---

**AdminKit** - Türk geliştiriciler için optimize edilmiş, EasyAdmin'den üstün enterprise admin panel çözümü.

### 🚀 Hemen Başlayın

```bash
composer require turkpin/admin-kit
php vendor/bin/adminkit install
```

**[📚 Dokümantasyon](docs/)** | **[🛠️ CLI Rehberi](docs/cli-tools.md)** | **[💡 Örnekler](docs/examples/)** | **[🏗️ Service Provider](docs/service-provider.md)**
