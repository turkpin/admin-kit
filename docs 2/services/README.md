# Enterprise Servisler

AdminKit'in güçlü enterprise servislerini keşfedin. Her servis, modern admin panel ihtiyaçlarınız için özel olarak tasarlanmıştır.

## 🏢 Enterprise Servis Kategorileri

### 🔐 Güvenlik Servisleri
- **[Authentication Service](auth-service.md)** - 2FA, session yönetimi ve güvenlik
- **[Audit Service](audit-service.md)** - Değişiklik takibi ve denetim logları

### ⚡ Performans Servisleri
- **[Queue Service](queue-service.md)** - Arkaplan işleme ve zamanlanmış görevler
- **[Performance Service](performance-service.md)** - İzleme, profiling ve optimizasyon
- **[Cache Service](cache-service.md)** - Çok katmanlı önbellekleme sistemi

### 🌐 Real-time Servisleri
- **[WebSocket Service](websocket-service.md)** - Gerçek zamanlı özellikler ve canlı güncellemeler
- **[Notification Service](notification-service.md)** - 5 kanallı bildirim sistemi

### 🎨 UI/UX Servisleri
- **[Asset Service](asset-service.md)** - Asset yönetimi ve build araçları
- **[Dynamic Forms](dynamic-forms.md)** - Koşullu alanlar ve çok adımlı formlar
- **[Breadcrumb Service](breadcrumb-service.md)** - Navigasyon ve breadcrumb yönetimi
- **[Theme Service](theme-service.md)** - Tema ve stil yönetimi

### 🔍 Veri Servisleri
- **[Filter Service](filter-service.md)** - Gelişmiş filtreleme ve arama
- **[Search Service](search-service.md)** - Global arama motoru
- **[Export/Import Service](export-import-service.md)** - Veri dışa/içe aktarma

### 🧩 Genişletme Servisleri
- **[Plugin Service](plugin-service.md)** - Plugin mimarisi ve hook sistemi
- **[Validation Service](validation-service.md)** - Gelişmiş doğrulama kuralları
- **[Localization Service](localization-service.md)** - Çok dil desteği

## 🚀 Hızlı Başlangıç

### Temel Servis Kullanımı

```php
<?php
use Turkpin\AdminKit\AdminKit;

$adminKit = new AdminKit($config);

// Queue Service'i etkinleştir
$queueService = $adminKit->getQueueService();
$queueService->dispatch('email', ['to' => 'user@example.com'], ['queue' => 'high']);

// Performance monitoring'i başlat
$performanceService = $adminKit->getPerformanceService();
$performanceService->startMonitoring();

// WebSocket'i etkinleştir
$webSocketService = $adminKit->getWebSocketService();
$webSocketService->start(['port' => 8080]);
```

### Service Container Kullanımı

```php
// Servis container'dan servis al
$notificationService = $adminKit->getService('notification');
$cacheService = $adminKit->getService('cache');
$auditService = $adminKit->getService('audit');

// Özel servis kaydet
$adminKit->registerService('custom', new CustomService());
```

## 📊 Servis Karşılaştırması

| Servis | EasyAdmin | AdminKit | Avantaj |
|--------|-----------|----------|---------|
| **Queue System** | ❌ | ✅ | 4 öncelik seviyesi + cron |
| **Real-time** | ❌ | ✅ | WebSocket + SSE fallback |
| **2FA Auth** | ❌ | ✅ | TOTP + backup kodları |
| **Performance Monitor** | ❌ | ✅ | Real-time metrics |
| **Asset Management** | ❌ | ✅ | Webpack/Vite entegrasyonu |
| **Dynamic Forms** | ❌ | ✅ | Conditional logic |
| **Advanced Filters** | ❌ | ✅ | 16 operatör + SQL preview |
| **Plugin Architecture** | ❌ | ✅ | Hook/Event system |
| **Multi-layer Cache** | ❌ | ✅ | Redis + File + Memory |
| **5-Channel Notifications** | ❌ | ✅ | Toast + Email + Database |

## 🎯 Kullanım Senaryoları

### E-ticaret Admin Paneli
```php
// Sipariş işleme için queue kullan
$adminKit->getQueueService()->dispatch('process_order', $orderData, ['queue' => 'high']);

// Real-time sipariş bildirimlerini etkinleştir
$adminKit->getWebSocketService()->joinChannel('orders');

// Ürün resimlerini otomatik optimize et
$adminKit->getAssetService()->optimizeImages(['quality' => 85]);
```

### Blog CMS Sistemi
```php
// İçerik yayınlama için zamanlanmış görev
$adminKit->getQueueService()->schedule('publish_post', $postData, '+1 hour');

// SEO için breadcrumb oluştur
$adminKit->getBreadcrumbService()->autoGenerate($request);

// İçerik filtreleme için gelişmiş arama
$adminKit->getFilterService()->buildQuery(['title' => 'contains', 'status' => 'published']);
```

### Kurumsal Dashboard
```php
// Performans metrikleri topla
$adminKit->getPerformanceService()->collectMetrics();

// Kullanıcı aktivitelerini denetle
$adminKit->getAuditService()->log('user_action', $actionData);

// Multi-step onboarding formu
$adminKit->getDynamicFormService()->createWizard(['steps' => 4]);
```

## 🔧 Konfigürasyon

### Global Servis Ayarları

```php
// config/services.php
return [
    'queue' => [
        'enabled' => true,
        'driver' => 'database', // database, redis, sqs
        'max_attempts' => 3,
        'retry_delay' => 60
    ],
    
    'websocket' => [
        'enabled' => true,
        'port' => 8080,
        'host' => '0.0.0.0',
        'auth_required' => true
    ],
    
    'performance' => [
        'enabled' => true,
        'slow_query_threshold' => 1000,
        'memory_limit_warning' => 80
    ],
    
    'cache' => [
        'enabled' => true,
        'default_driver' => 'redis',
        'ttl' => 3600
    ]
];
```

### Servis Dependency Injection

```php
class CustomController
{
    public function __construct(
        private QueueService $queue,
        private NotificationService $notification,
        private CacheService $cache
    ) {}
    
    public function processData($data)
    {
        // Cache'den kontrol et
        $cached = $this->cache->get('processed_data_' . $data['id']);
        if ($cached) {
            return $cached;
        }
        
        // Queue'ya ekle
        $this->queue->dispatch('process_data', $data);
        
        // Bildirim gönder
        $this->notification->send('admin', [
            'title' => 'Data Processing Started',
            'message' => 'Data processing has been queued'
        ]);
    }
}
```

## 🛠️ Geliştirici Araçları

### Service Debug Modu

```php
// Development ortamında debug etkinleştir
$adminKit->enableServiceDebug();

// Servis çağrılarını logla
$adminKit->getService('debug')->logServiceCalls();

// Performans metrikleri göster
$adminKit->getService('debug')->showPerformanceMetrics();
```

### Service Testing

```php
// Test ortamında mock servisler kullan
$adminKit->setEnvironment('testing');
$adminKit->mockService('queue', new MockQueueService());
$adminKit->mockService('notification', new MockNotificationService());
```

## 📈 Monitoring ve Analytics

### Servis Durumu İzleme

```php
// Tüm servislerin durumunu kontrol et
$status = $adminKit->getServiceStatus();

// Belirli servis durumu
$queueStatus = $adminKit->getQueueService()->getStatus();
$websocketStatus = $adminKit->getWebSocketService()->getConnectionCount();
```

### Servis Metrikleri

```php
// Servis performans metrikleri
$metrics = $adminKit->getServiceMetrics([
    'queue' => ['pending_jobs', 'completed_jobs', 'failed_jobs'],
    'cache' => ['hit_rate', 'miss_rate', 'memory_usage'],
    'websocket' => ['active_connections', 'message_rate']
]);
```

## 🔗 Service Chain'leri

### Event-Driven Architecture

```php
// Event chain oluştur
$adminKit->onEvent('user.created', function($user) {
    // Queue'ya hoş geldin emaili ekle
    $this->queue->dispatch('welcome_email', ['user_id' => $user['id']]);
    
    // Audit log kaydet
    $this->audit->log('user_created', $user);
    
    // Real-time bildirim gönder
    $this->websocket->broadcast('admin', ['type' => 'user_created', 'user' => $user]);
    
    // Cache'i güncelle
    $this->cache->forget('user_count');
});
```

## 💡 Best Practices

### 1. Servis Performansı
- Cache'i etkin kullanın
- Queue'yu CPU yoğun işlemler için kullanın
- WebSocket bağlantılarını sınırlayın

### 2. Güvenlik
- 2FA'yı production'da etkinleştirin
- Audit logging'i kritik işlemler için kullanın
- Rate limiting uygulayın

### 3. Monitoring
- Performance Service'i sürekli aktif tutun
- Critical job'lar için alert kurun
- Resource usage'ı takip edin

## 🆘 Troubleshooting

### Yaygın Sorunlar

1. **Queue jobs çalışmıyor**
   - Worker'ın çalıştığından emin olun: `php artisan queue:work`
   - Database/Redis bağlantısını kontrol edin

2. **WebSocket bağlantısı kesiliyior**
   - Port'un açık olduğunu kontrol edin
   - Firewall kurallarını kontrol edin

3. **Cache çalışmıyor**
   - Redis/Memcached servisinin çalıştığını kontrol edin
   - Cache config'ini doğrulayın

---

AdminKit'in enterprise servisleri ile modern, ölçeklenebilir ve güvenli admin paneller oluşturun.
