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

## 📚 Dokümantasyon

AdminKit kapsamlı dokümantasyon sistemine sahiptir:

### 🚀 Başlangıç Rehberleri
- **[Kurulum Rehberi](docs/installation.md)** - Sistem gereksinimleri ve adım adım kurulum
- **[Hızlı Başlangıç](docs/quick-start.md)** - 5 dakikada çalışan blog sistemi
- **[Konfigürasyon](docs/configuration.md)** - Detaylı yapılandırma seçenekleri

### 📖 Kullanım Kılavuzları
- **[Alan Türleri](docs/field-types.md)** - 14 farklı alan türü ve kullanımları
- **[API Referansı](docs/api-reference.md)** - Tam API dokümantasyonu
- **[Deployment](docs/deployment.md)** - Production ortamına yayınlama

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
- **[Daha Fazla Örnek](docs/examples/)** - Hazır kullanıma ready projeler

### 🔧 Gelişmiş Konular
- **[Güvenlik](docs/advanced/security.md)** - 2FA ve güvenlik best practices
- **[Performans](docs/advanced/performance.md)** - Optimizasyon teknikleri
- **[Özelleştirme](docs/advanced/customization.md)** - Theme ve UI özelleştirme

**➡️ [Tüm Dokümantasyon](docs/)** - Kapsamlı rehber ve örnekler

## Özellikler

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

## Sistem Gereksinimleri

- PHP 8.1 veya üzeri
- MySQL 5.7+ veya PostgreSQL 12+
- Composer
- Web sunucusu (Apache/Nginx)

## Kurulum

```bash
composer require turkpin/admin-kit
```

**Detaylı kurulum için**: [Kurulum Rehberi](docs/installation.md)

## Temel Kullanım

### 1. Basit Kurulum

```php
<?php
require_once 'vendor/autoload.php';

use Turkpin\AdminKit\AdminKit;

// AdminKit'i başlat
$adminKit = new AdminKit([
    'database' => [
        'driver' => 'mysql',
        'host' => 'localhost',
        'database' => 'admin_db',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4'
    ],
    'auth' => [
        'enabled' => true,
        '2fa_enabled' => true
    ],
    'cache' => [
        'enabled' => true,
        'driver' => 'file'
    ],
    'websocket' => [
        'enabled' => true,
        'port' => 8080
    ]
]);

// Entity tanımla
$adminKit->addEntity('User', [
    'table' => 'users',
    'fields' => [
        'name' => ['type' => 'text', 'label' => 'Ad Soyad', 'required' => true],
        'email' => ['type' => 'email', 'label' => 'E-posta', 'required' => true],
        'phone' => ['type' => 'text', 'label' => 'Telefon'],
        'avatar' => ['type' => 'image', 'label' => 'Profil Fotoğrafı'],
        'is_active' => ['type' => 'boolean', 'label' => 'Aktif'],
        'created_at' => ['type' => 'datetime', 'label' => 'Kayıt Tarihi']
    ],
    'list_fields' => ['name', 'email', 'is_active', 'created_at'],
    'filters' => ['name', 'email', 'is_active'],
    'searchable' => ['name', 'email'],
    'sortable' => ['name', 'email', 'created_at']
]);

// Dashboard widget'ı ekle
$adminKit->addWidget('user_count', [
    'title' => 'Toplam Kullanıcı',
    'value' => function() use ($adminKit) {
        return $adminKit->getEntityCount('User');
    },
    'color' => 'blue'
]);

// Uygulamayı çalıştır
$adminKit->run();
```

**Daha fazla örnek için**: [Hızlı Başlangıç](docs/quick-start.md)

### 2. Enterprise Özellikler

```php
// Queue sistemi
$adminKit->dispatchJob('email', [
    'to' => 'user@example.com',
    'subject' => 'Hoş Geldiniz'
], ['queue' => 'high']);

// 2FA etkinleştir
$adminKit->enable2FA();

// Real-time bildirim gönder
$adminKit->getWebSocketService()->sendToUser(123, [
    'title' => 'Yeni Mesaj',
    'message' => 'Size yeni bir mesaj geldi'
]);

// Performans izleme
$metrics = $adminKit->getPerformanceService()->getMetrics();
```

**Enterprise özellikler için**: [Servis Dokümantasyonu](docs/services/)

## Field Types

AdminKit 14 farklı alan tipini destekler:

### Text Fields
- `text`: Tek satır metin
- `textarea`: Çok satır metin
- `email`: E-posta adresi
- `password`: Şifre (güçlü şifre göstergesi ile)

### Numeric Fields
- `number`: Sayı
- `money`: Para birimi

### Date Fields
- `date`: Tarih seçici
- `datetime`: Tarih ve saat seçici

### Boolean & Choice
- `boolean`: Açık/kapalı
- `choice`: Seçenek listesi

### File Fields
- `file`: Dosya yükleme
- `image`: Resim yükleme (kırpma ve önizleme ile)

### Relation Fields
- `association`: Entity ilişkisi (autocomplete ile)
- `collection`: Çoklu form koleksiyonu

**Detaylı kullanım için**: [Alan Türleri Dokümantasyonu](docs/field-types.md)

## API Kullanımı

AdminKit otomatik REST API endpoints sağlar:

```bash
# Kullanıcıları listele
GET /api/users

# Kullanıcı detayı
GET /api/users/{id}

# Yeni kullanıcı oluştur
POST /api/users

# Kullanıcı güncelle
PUT /api/users/{id}

# Kullanıcı sil
DELETE /api/users/{id}

# Arama yap
GET /api/users?search=ahmet

# Filtreleme
GET /api/users?filter[is_active]=true

# Server-Sent Events endpoint
GET /api/sse-messages

# WebSocket bağlantı bilgileri
GET /api/websocket/info
```

**API detayları için**: [API Referansı](docs/api-reference.md)

## Internationalization (i18n)

AdminKit 600+ çeviri anahtarı ile tam Türkçe ve İngilizce desteği sunar:

```php
// Dil dosyasından çeviri al
$localization = $adminKit->getLocalizationService();
echo $localization->get('user_created'); // "Kullanıcı başarıyla oluşturuldu."

// Parametreli çeviri
echo $localization->get('welcome_message', ['name' => 'Ahmet']); // "Hoş geldiniz, Ahmet!"

// Dil değiştir
$localization->setLocale('en'); // İngilizce'ye geç
```

**Çok dil desteği için**: [Internationalization Rehberi](docs/advanced/internationalization.md)

## Production Deployment

```bash
# Production optimizasyonları
composer install --no-dev --optimize-autoloader
php vendor/bin/adminkit cache:warm
php vendor/bin/adminkit assets:build

# WebSocket sunucusunu başlat
php vendor/bin/adminkit websocket:start

# Queue worker'ını başlat
php vendor/bin/adminkit queue:work
```

**Production setup için**: [Deployment Rehberi](docs/deployment.md)

## Lisans

MIT License. Detaylar için [LICENSE](LICENSE) dosyasına bakın.

## Destek

Sorularınız için:
- **[Dokümantasyon](docs/)** - Kapsamlı rehberler ve örnekler
- **GitHub Issues**: [admin-kit/issues](https://github.com/turkpin/admin-kit/issues)
- **E-posta**: support@turkpin.com
- **Türkçe Destek**: Tam Türkçe dokümantasyon ve topluluk desteği

## Katkıda Bulunma

AdminKit açık kaynak bir projedir. Katkılarınızı bekliyoruz!

1. Repository'yi fork edin
2. Feature branch oluşturun (`git checkout -b feature/amazing-feature`)
3. Değişikliklerinizi commit edin (`git commit -m 'feat: add amazing feature'`)
4. Branch'inizi push edin (`git push origin feature/amazing-feature`)
5. Pull Request oluşturun

Katkıda bulunmadan önce [CONTRIBUTING.md](CONTRIBUTING.md) dosyasını okuyun.

---

**AdminKit** - Türk geliştiriciler için optimize edilmiş, EasyAdmin'den üstün enterprise admin panel çözümü.

**[📚 Dokümantasyona Başla](docs/)** | **[🚀 Hızlı Kurulum](docs/installation.md)** | **[💡 Örnekleri İncele](docs/examples/)**
