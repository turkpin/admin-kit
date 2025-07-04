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

### 2. Gelişmiş Entity Yapılandırması

```php
$adminKit->addEntity('Product', [
    'table' => 'products',
    'title' => 'Ürünler',
    'fields' => [
        'name' => [
            'type' => 'text',
            'label' => 'Ürün Adı',
            'required' => true,
            'max_length' => 255
        ],
        'description' => [
            'type' => 'textarea',
            'label' => 'Açıklama',
            'rows' => 5
        ],
        'price' => [
            'type' => 'number',
            'label' => 'Fiyat',
            'min' => 0,
            'step' => 0.01,
            'currency' => 'TL'
        ],
        'image' => [
            'type' => 'image',
            'label' => 'Ürün Resmi',
            'upload_path' => 'uploads/products',
            'allowed_types' => ['jpg', 'png', 'webp'],
            'max_size' => '2MB'
        ],
        'category_id' => [
            'type' => 'association',
            'label' => 'Kategori',
            'target_entity' => 'Category',
            'display_field' => 'name'
        ],
        'tags' => [
            'type' => 'association',
            'label' => 'Etiketler',
            'target_entity' => 'Tag',
            'multiple' => true
        ],
        'is_featured' => [
            'type' => 'boolean',
            'label' => 'Öne Çıkan'
        ],
        'status' => [
            'type' => 'choice',
            'label' => 'Durum',
            'choices' => [
                'draft' => 'Taslak',
                'published' => 'Yayında',
                'archived' => 'Arşivlendi'
            ]
        ]
    ],
    'permissions' => ['product.view', 'product.create', 'product.edit', 'product.delete']
]);
```

### 3. Kullanıcı Rolleri ve İzinler

```php
// Roller oluştur
$adminKit->createRole('admin', 'Yönetici');
$adminKit->createRole('editor', 'Editör');
$adminKit->createRole('viewer', 'Görüntüleyici');

// İzinler oluştur
$adminKit->createPermission('user.view', 'Kullanıcıları Görüntüle');
$adminKit->createPermission('user.create', 'Kullanıcı Oluştur');
$adminKit->createPermission('user.edit', 'Kullanıcı Düzenle');
$adminKit->createPermission('user.delete', 'Kullanıcı Sil');

// Role izin ata
$adminKit->assignPermissionToRole('admin', ['user.view', 'user.create', 'user.edit', 'user.delete']);
$adminKit->assignPermissionToRole('editor', ['user.view', 'user.edit']);
$adminKit->assignPermissionToRole('viewer', ['user.view']);

// Kullanıcıya rol ata
$adminKit->assignRoleToUser($userId, 'admin');
```

### 4. Background Jobs ve Queue Sistemi

```php
// E-posta gönderme işini kuyruğa ekle
$adminKit->dispatchJob('email', [
    'to' => 'user@example.com',
    'subject' => 'Hoş Geldiniz',
    'template' => 'welcome_email',
    'data' => ['name' => 'Ahmet Yılmaz']
], ['queue' => 'high', 'delay' => 0]);

// Veri dışa aktarma işini zamanla
$adminKit->dispatchJob('export', [
    'entity' => 'User',
    'format' => 'excel',
    'filters' => ['is_active' => true]
], ['queue' => 'default', 'delay' => 300]); // 5 dakika sonra

// Tekrarlanan temizlik işi zamanla
$adminKit->scheduleJob('cleanup', [
    'type' => 'temp_files'
], '@daily'); // Her gün çalıştır
```

### 5. Performance İzleme

```php
// Performans metrikleri al
$performance = $adminKit->getPerformanceService();

// Yavaş sorguları görüntüle
$slowQueries = $performance->getSlowQueries(24); // Son 24 saat

// Sistem durumunu kontrol et
$systemHealth = $performance->getSystemHealth();

// Özel metrik kaydet
$performance->recordMetric('user_login', 1, ['ip' => $userIp]);
```

### 6. Dynamic Forms ve Conditional Fields

```php
// Dinamik form oluştur
$dynamicForm = $adminKit->getDynamicFormService();

$dynamicForm->registerForm('user_registration', [
    'title' => 'Kullanıcı Kaydı',
    'description' => 'Yeni kullanıcı kayıt formu',
    'steps' => [
        [
            'title' => 'Kişisel Bilgiler',
            'description' => 'Temel bilgilerinizi girin',
            'fields' => [
                'name' => ['type' => 'text', 'label' => 'Ad Soyad', 'required' => true],
                'email' => ['type' => 'email', 'label' => 'E-posta', 'required' => true],
                'user_type' => [
                    'type' => 'choice',
                    'label' => 'Kullanıcı Tipi',
                    'choices' => ['individual' => 'Bireysel', 'corporate' => 'Kurumsal']
                ]
            ]
        ],
        [
            'title' => 'Ek Bilgiler',
            'fields' => [
                'company_name' => [
                    'type' => 'text',
                    'label' => 'Şirket Adı',
                    'required' => true
                ],
                'tax_number' => ['type' => 'text', 'label' => 'Vergi Numarası']
            ]
        ]
    ],
    'ajax_validation' => true,
    'auto_save' => true
]);

// Koşullu alan mantığı ekle
$dynamicForm->addCondition('user_registration', 'company_name', [
    'dependsOn' => 'user_type',
    'operator' => 'equals',
    'value' => 'corporate',
    'action' => 'show',
    'animation' => 'fade'
]);
```

### 7. Real-time Features ve WebSocket

```php
// WebSocket sunucusunu başlat
$webSocket = $adminKit->getWebSocketService();

// Gerçek zamanlı bildirim gönder
$webSocket->sendToUser(123, [
    'title' => 'Yeni Mesaj',
    'message' => 'Size yeni bir mesaj geldi',
    'type' => 'info'
], 'notification');

// Tüm kullanıcılara yayın yap
$webSocket->broadcast('system', [
    'title' => 'Sistem Duyurusu',
    'message' => 'Sistem bakımı 30 dakika içinde başlayacak'
]);

// Kullanıcı varlığını takip et
$webSocket->updateUserPresence(123, 'online');
```

### 8. Asset Management

```php
// Asset yöneticisini al
$assetService = $adminKit->getAssetService();

// Yeni asset kaydet
$assetService->registerAsset('custom-dashboard.css', [
    'type' => 'css',
    'path' => 'css/custom-dashboard.css',
    'dependencies' => ['admin-core.css'],
    'priority' => 85,
    'critical' => true
]);

// Asset'leri derle
$results = $assetService->compile();

// CSS asset'lerini render et
echo $assetService->renderCss();

// JavaScript asset'lerini render et
echo $assetService->renderJs();
```

## Konfigürasyon

### Temel Konfigürasyon

```php
$config = [
    'app_name' => 'AdminKit Panel',
    'app_url' => 'https://admin.example.com',
    'timezone' => 'Europe/Istanbul',
    'locale' => 'tr',
    
    'database' => [
        'driver' => 'mysql',
        'host' => 'localhost',
        'port' => 3306,
        'database' => 'admin_db',
        'username' => 'admin_user',
        'password' => 'secure_password',
        'charset' => 'utf8mb4'
    ],
    
    'auth' => [
        'enabled' => true,
        'login_route' => '/admin/login',
        'logout_route' => '/admin/logout',
        'session_timeout' => 7200, // 2 saat
        '2fa_enabled' => true,
        'password_min_length' => 8
    ],
    
    'cache' => [
        'enabled' => true,
        'driver' => 'redis', // file, redis, memcached
        'ttl' => 3600,
        'redis' => [
            'host' => 'localhost',
            'port' => 6379,
            'database' => 0
        ]
    ],
    
    'websocket' => [
        'enabled' => true,
        'port' => 8080,
        'host' => '0.0.0.0',
        'max_connections' => 1000,
        'auth_required' => true,
        'fallback_polling' => true
    ],
    
    'assets' => [
        'enabled' => true,
        'versioning' => true,
        'minification' => true,
        'compression' => true,
        'cdn_enabled' => false,
        'cdn_url' => ''
    ],
    
    'uploads' => [
        'path' => 'public/uploads',
        'max_size' => '10MB',
        'allowed_types' => ['jpg', 'png', 'gif', 'pdf', 'docx']
    ],
    
    'notifications' => [
        'email_enabled' => true,
        'smtp' => [
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'username' => 'noreply@example.com',
            'password' => 'app_password',
            'encryption' => 'tls'
        ]
    ]
];
```

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

## Migration ve Deployment

### Veritabanı Migration

```bash
# Migration dosyalarını çalıştır
php vendor/bin/adminkit migrate

# Seed verilerini yükle
php vendor/bin/adminkit seed

# Cache'i temizle
php vendor/bin/adminkit cache:clear
```

### Production Deployment

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

### Yeni Çeviri Ekleme

```php
// src/Translations/tr.php
return [
    'my_custom_key' => 'Özel mesajım',
    'parameterized_message' => 'Merhaba :name, hoş geldiniz!'
];
```

## Lisans

MIT License. Detaylar için [LICENSE](LICENSE) dosyasına bakın.

## Destek

Sorularınız için:
- GitHub Issues: [https://github.com/turkpin/admin-kit/issues](https://github.com/turkpin/admin-kit/issues)
- E-posta: support@turkpin.com
- Dokümantasyon: [https://docs.turkpin.com/admin-kit](https://docs.turkpin.com/admin-kit)

## Katkıda Bulunma

1. Repository'yi fork edin
2. Feature branch oluşturun (`git checkout -b feature/amazing-feature`)
3. Değişikliklerinizi commit edin (`git commit -m 'feat: add amazing feature'`)
4. Branch'inizi push edin (`git push origin feature/amazing-feature`)
5. Pull Request oluşturun

Katkıda bulunmadan önce [CONTRIBUTING.md](CONTRIBUTING.md) dosyasını okuyun.
