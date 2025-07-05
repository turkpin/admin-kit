# Hızlı Başlangıç Rehberi

Bu rehber ile AdminKit'i 5 dakikada kullanmaya başlayabilirsiniz.

## Önkoşullar

- AdminKit'in kurulu olduğundan emin olun ([Kurulum Rehberi](installation.md))
- Veritabanı bağlantısının çalıştığından emin olun
- Web sunucunuzun çalıştığından emin olun

## 5 Dakikalık Demo

### 1. Basit Blog Sistemi (2 dakika)

İlk örneğimizde basit bir blog sistemi oluşturacağız.

```php
<?php
// public/index.php
require_once '../vendor/autoload.php';

use Turkpin\AdminKit\AdminKit;

$config = require '../config/app.php';
$adminKit = new AdminKit($config);

// Blog kategorileri
$adminKit->addEntity('Category', [
    'table' => 'categories',
    'title' => 'Kategoriler',
    'fields' => [
        'name' => [
            'type' => 'text',
            'label' => 'Kategori Adı',
            'required' => true,
            'max_length' => 100
        ],
        'slug' => [
            'type' => 'text',
            'label' => 'URL Slug',
            'help' => 'URL\'de görünecek isim'
        ],
        'description' => [
            'type' => 'textarea',
            'label' => 'Açıklama',
            'rows' => 3
        ],
        'is_active' => [
            'type' => 'boolean',
            'label' => 'Aktif',
            'default' => true
        ]
    ],
    'list_fields' => ['name', 'slug', 'is_active'],
    'searchable' => ['name', 'description']
]);

// Blog yazıları
$adminKit->addEntity('Post', [
    'table' => 'posts',
    'title' => 'Blog Yazıları',
    'fields' => [
        'title' => [
            'type' => 'text',
            'label' => 'Başlık',
            'required' => true,
            'max_length' => 200
        ],
        'slug' => [
            'type' => 'text',
            'label' => 'URL Slug'
        ],
        'content' => [
            'type' => 'textarea',
            'label' => 'İçerik',
            'rows' => 10,
            'required' => true
        ],
        'excerpt' => [
            'type' => 'textarea',
            'label' => 'Özet',
            'rows' => 3,
            'help' => 'Yazının kısa özeti'
        ],
        'featured_image' => [
            'type' => 'image',
            'label' => 'Öne Çıkan Resim',
            'upload_path' => 'uploads/posts',
            'max_size' => '2MB'
        ],
        'category_id' => [
            'type' => 'association',
            'label' => 'Kategori',
            'target_entity' => 'Category',
            'display_field' => 'name',
            'required' => true
        ],
        'tags' => [
            'type' => 'text',
            'label' => 'Etiketler',
            'help' => 'Virgülle ayırın: php, web, teknoloji'
        ],
        'status' => [
            'type' => 'choice',
            'label' => 'Durum',
            'choices' => [
                'draft' => 'Taslak',
                'published' => 'Yayında',
                'archived' => 'Arşivlendi'
            ],
            'default' => 'draft'
        ],
        'published_at' => [
            'type' => 'datetime',
            'label' => 'Yayın Tarihi'
        ],
        'created_at' => [
            'type' => 'datetime',
            'label' => 'Oluşturulma Tarihi'
        ]
    ],
    'list_fields' => ['title', 'category_id', 'status', 'published_at'],
    'filters' => ['category_id', 'status'],
    'searchable' => ['title', 'content', 'tags']
]);

$adminKit->run();
```

### 2. Veritabanı Tablolarını Oluştur (1 dakika)

```sql
-- categories tablosu
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- posts tablosu
CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(200) UNIQUE,
    content LONGTEXT NOT NULL,
    excerpt TEXT,
    featured_image VARCHAR(255),
    category_id INT,
    tags TEXT,
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);
```

### 3. Test Verileri Ekle (1 dakika)

```sql
-- Örnek kategoriler
INSERT INTO categories (name, slug, description, is_active) VALUES
('Teknoloji', 'teknoloji', 'Teknoloji ile ilgili yazılar', TRUE),
('Web Geliştirme', 'web-gelistirme', 'Web development konuları', TRUE),
('PHP', 'php', 'PHP programlama dili', TRUE);

-- Örnek yazılar
INSERT INTO posts (title, slug, content, excerpt, category_id, tags, status, published_at) VALUES
(
    'AdminKit ile Hızlı Admin Panel Geliştirme',
    'adminkit-hizli-admin-panel',
    'AdminKit kullanarak nasıl hızlı bir şekilde admin panel geliştirebileceğinizi öğrenin...',
    'AdminKit\'in avantajları ve kullanım örnekleri',
    1,
    'adminkit, php, admin panel',
    'published',
    NOW()
),
(
    'Modern PHP Geliştirme Teknikleri',
    'modern-php-gelistirme',
    'PHP 8+ ile gelen yeni özellikler ve modern geliştirme yaklaşımları...',
    'PHP\'nin son sürümlerindeki yenilikler',
    3,
    'php, modern, geliştirme',
    'published',
    NOW()
);
```

### 4. Panel'e Giriş Yap (30 saniye)

1. Tarayıcınızda `http://localhost/admin` adresine gidin
2. Varsayılan giriş bilgileri:
   - **Email**: admin@example.com
   - **Şifre**: admin123
3. Dashboard'u görüntüleyin

### 5. Test Et (30 saniye)

- **Kategoriler** bölümünden yeni kategori ekleyin
- **Blog Yazıları** bölümünden yeni yazı oluşturun
- Filtreleme ve arama özelliklerini test edin

## İleri Seviye Özellikler (5 dakika)

### Enterprise Özellikleri Etkinleştir

```php
// 2FA (İki Faktörlü Kimlik Doğrulama)
$adminKit->enable2FA();

// Audit logging (Değişiklik takibi)
$adminKit->enableAuditLogging();

// Performance monitoring
$adminKit->enablePerformanceMonitoring();

// Real-time notifications
$adminKit->enableWebSocket([
    'port' => 8080,
    'host' => '0.0.0.0'
]);
```

### Dashboard Widget'ları Ekle

```php
// Toplam yazı sayısı
$adminKit->addWidget('total_posts', [
    'title' => 'Toplam Yazı',
    'value' => function() use ($adminKit) {
        return $adminKit->getEntityCount('Post');
    },
    'color' => 'blue',
    'icon' => 'document-text'
]);

// Yayınlanan yazılar
$adminKit->addWidget('published_posts', [
    'title' => 'Yayınlanan Yazılar',
    'value' => function() use ($adminKit) {
        return $adminKit->getEntityCount('Post', ['status' => 'published']);
    },
    'color' => 'green',
    'icon' => 'check-circle'
]);

// Bu hafta eklenen yazılar
$adminKit->addWidget('weekly_posts', [
    'title' => 'Bu Hafta',
    'value' => function() use ($adminKit) {
        $oneWeekAgo = date('Y-m-d H:i:s', strtotime('-1 week'));
        return $adminKit->getEntityCount('Post', [
            'created_at >=' => $oneWeekAgo
        ]);
    },
    'color' => 'yellow',
    'icon' => 'calendar'
]);
```

### Gelişmiş Filtreleme Ekle

```php
// Posts entity'sine gelişmiş filtreler
$adminKit->updateEntity('Post', [
    'advanced_filters' => [
        [
            'field' => 'created_at',
            'operator' => 'between',
            'label' => 'Oluşturulma Tarihi Aralığı'
        ],
        [
            'field' => 'title',
            'operator' => 'contains',
            'label' => 'Başlıkta Ara'
        ],
        [
            'field' => 'tags',
            'operator' => 'contains',
            'label' => 'Etiket Ara'
        ]
    ]
]);
```

### Çoklu Dil Desteği

```php
// Dil seçenekleri ekle
$adminKit->addLanguageSwitcher([
    'tr' => 'Türkçe',
    'en' => 'English'
]);

// Varsayılan dili ayarla
$adminKit->setDefaultLocale('tr');
```

## Özelleştirme Örnekleri

### 1. Özel Tema

```php
$adminKit->setTheme('dark'); // dark, light, blue, green
```

### 2. Özel Menü

```php
$adminKit->addMenuItem('analytics', [
    'title' => 'Analitik',
    'url' => '/admin/analytics',
    'icon' => 'chart-bar',
    'permission' => 'analytics.view'
]);
```

### 3. Özel Validasyon

```php
$adminKit->addValidationRule('Post', 'title', function($value) {
    if (strlen($value) < 10) {
        return 'Başlık en az 10 karakter olmalıdır';
    }
    return true;
});
```

### 4. Özel Event Handler

```php
$adminKit->onEntityCreate('Post', function($data) {
    // Yeni yazı oluşturulduğunda bildirim gönder
    $notification = $adminKit->getNotificationService();
    $notification->send('admin', [
        'title' => 'Yeni Blog Yazısı',
        'message' => "'{$data['title']}' başlıklı yazı oluşturuldu",
        'type' => 'info'
    ]);
});
```

## Sonraki Adımlar

Tebrikler! AdminKit'i başarıyla kurduğunuz ve ilk projenizi oluşturdunuz. Şimdi:

1. **[Alan Türleri](field-types.md)** - 14 farklı alan türünü keşfedin
2. **[Enterprise Servisler](services/)** - Güçlü enterprise özelliklerini öğrenin
3. **[Örnekler](examples/)** - Daha kapsamlı proje örneklerini inceleyin
4. **[Güvenlik](advanced/security.md)** - Güvenlik ayarlarını yapılandırın

## Sorun mu Yaşıyorsunuz?

- **[Sorun Giderme](troubleshooting.md)** - Yaygın sorunlar ve çözümler
- **[Konfigürasyon](configuration.md)** - Detaylı ayar seçenekleri
- **GitHub Issues** - Topluluk desteği

---

**AdminKit ile hızlı ve güçlü admin paneller geliştirmenin keyfini çıkarın!** 🚀
