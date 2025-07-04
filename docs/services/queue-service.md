# Queue Service

AdminKit'in Queue Service'i arkaplan işleme, zamanlanmış görevler ve asenkron operasyonlar için güçlü bir çözüm sunar.

## 🚀 Hızlı Başlangıç

### Temel Kullanım

```php
use Turkpin\AdminKit\AdminKit;

$adminKit = new AdminKit($config);
$queueService = $adminKit->getQueueService();

// Basit iş gönder
$queueService->dispatch('email', [
    'to' => 'user@example.com',
    'subject' => 'Hoş Geldiniz',
    'body' => 'AdminKit\'e hoş geldiniz!'
]);

// Öncelikli iş gönder
$queueService->dispatch('urgent_notification', $data, [
    'queue' => 'high',
    'delay' => 0
]);
```

### Konfigürasyon

```php
// config/queue.php
return [
    'enabled' => true,
    'driver' => 'database', // database, redis, sqs
    'table' => 'jobs',
    'max_attempts' => 3,
    'retry_delay' => 60, // saniye
    'timeout' => 300,    // saniye
    
    'queues' => [
        'critical' => ['priority' => 1, 'timeout' => 120],
        'high'     => ['priority' => 2, 'timeout' => 180],
        'default'  => ['priority' => 3, 'timeout' => 300],
        'low'      => ['priority' => 4, 'timeout' => 600]
    ],
    
    'workers' => [
        'count' => 3,
        'memory_limit' => '256M',
        'sleep' => 3,
        'max_jobs' => 1000
    ]
];
```

## 📋 Kuyruk Türleri

### 1. Critical Queue
En yüksek öncelik - kritik sistemsel işlemler

```php
$queueService->dispatch('system_backup', $backupData, [
    'queue' => 'critical',
    'timeout' => 3600 // 1 saat
]);
```

### 2. High Priority Queue
Yüksek öncelik - kullanıcı deneyimini etkileyen işlemler

```php
$queueService->dispatch('password_reset', $userData, [
    'queue' => 'high',
    'delay' => 0
]);
```

### 3. Default Queue
Normal öncelik - standart işlemler

```php
$queueService->dispatch('export_data', $exportParams, [
    'queue' => 'default'
]);
```

### 4. Low Priority Queue
Düşük öncelik - arka plan temizlik işlemleri

```php
$queueService->dispatch('cleanup_logs', [], [
    'queue' => 'low',
    'delay' => 3600 // 1 saat sonra
]);
```

## 🛠️ İş Türleri (Job Types)

### E-posta İşleri

```php
// Tekil e-posta
$queueService->dispatch('email', [
    'to' => 'user@example.com',
    'template' => 'welcome',
    'data' => ['name' => 'Ahmet Yılmaz']
], ['queue' => 'high']);

// Toplu e-posta
$queueService->dispatch('bulk_email', [
    'recipients' => ['user1@example.com', 'user2@example.com'],
    'template' => 'newsletter',
    'data' => ['month' => 'Ocak 2025']
], ['queue' => 'default']);
```

### Dışa Aktarma İşleri

```php
$queueService->dispatch('export', [
    'entity' => 'User',
    'format' => 'excel',
    'filters' => ['is_active' => true],
    'user_id' => $currentUserId,
    'filename' => 'users_' . date('Y-m-d') . '.xlsx'
], ['queue' => 'default', 'timeout' => 1800]);
```

### İçe Aktarma İşleri

```php
$queueService->dispatch('import', [
    'file_path' => 'uploads/import/users.csv',
    'entity' => 'User',
    'mapping' => [
        'name' => 'ad_soyad',
        'email' => 'eposta',
        'phone' => 'telefon'
    ],
    'user_id' => $currentUserId
], ['queue' => 'default', 'timeout' => 3600]);
```

### Bildirim İşleri

```php
$queueService->dispatch('notification', [
    'type' => 'push',
    'recipient_ids' => [1, 2, 3, 4, 5],
    'title' => 'Sistem Duyurusu',
    'message' => 'Bakım çalışması 2 saat sonra başlayacak',
    'data' => ['action' => 'maintenance_notice']
], ['queue' => 'high']);
```

### Temizlik İşleri

```php
$queueService->dispatch('cleanup', [
    'type' => 'temp_files',
    'older_than' => '7 days'
], ['queue' => 'low', 'delay' => 3600]);

$queueService->dispatch('cleanup', [
    'type' => 'logs',
    'older_than' => '30 days'
], ['queue' => 'low', 'delay' => 7200]);
```

## ⏰ Zamanlanmış Görevler (Cron Jobs)

### Tekrarlanan Görevler

```php
// Günlük rapor
$queueService->schedule('daily_report', [], [
    'cron' => '0 9 * * *', // Her gün 09:00
    'queue' => 'default'
]);

// Haftalık backup
$queueService->schedule('weekly_backup', [], [
    'cron' => '0 2 * * 0', // Her pazar 02:00
    'queue' => 'critical'
]);

// Aylık temizlik
$queueService->schedule('monthly_cleanup', [], [
    'cron' => '0 3 1 * *', // Her ayın 1'i 03:00
    'queue' => 'low'
]);
```

### Dinamik Zamanlama

```php
// 1 saat sonra çalıştır
$queueService->scheduleAt('send_reminder', $reminderData, '+1 hour');

// Belirli tarihte çalıştır
$queueService->scheduleAt('birthday_greeting', $userData, '2025-01-15 09:00:00');

// Tekrarlanan görev
$queueService->scheduleEvery('check_system_health', [], [
    'interval' => '5 minutes',
    'queue' => 'high'
]);
```

## 📊 Queue Dashboard

### Gerçek Zamanlı Durum

```php
// Queue durumunu al
$status = $queueService->getStatus();
/*
Array(
    'pending_jobs' => 15,
    'completed_jobs' => 1250,
    'failed_jobs' => 3,
    'total_jobs' => 1268,
    'workers' => [
        ['id' => 1, 'status' => 'running', 'current_job' => 'email'],
        ['id' => 2, 'status' => 'idle', 'current_job' => null],
        ['id' => 3, 'status' => 'running', 'current_job' => 'export']
    ],
    'queues' => [
        'critical' => ['pending' => 0, 'running' => 0],
        'high' => ['pending' => 5, 'running' => 1],
        'default' => ['pending' => 8, 'running' => 2],
        'low' => ['pending' => 2, 'running' => 0]
    ]
)
*/
```

### İstatistikler

```php
// Son 24 saatin istatistikleri
$stats = $queueService->getStatistics('24h');

// Başarısız işleri listele
$failedJobs = $queueService->getFailedJobs();

// Uzun süren işleri bul
$slowJobs = $queueService->getSlowJobs(['threshold' => 300]);
```

## 🔧 Worker Yönetimi

### Worker Başlatma

```bash
# Tek worker başlat
php vendor/bin/adminkit queue:work

# Belirli queue için worker
php vendor/bin/adminkit queue:work --queue=high

# Çoklu worker başlat
php vendor/bin/adminkit queue:work --workers=3

# Memory limit ile
php vendor/bin/adminkit queue:work --memory=512
```

### Worker Konfigürasyonu

```php
// Worker başlatma (PHP içinden)
$queueService->startWorker([
    'queue' => 'default',
    'memory' => '256M',
    'timeout' => 300,
    'sleep' => 3,
    'max_jobs' => 1000
]);

// Worker durdurma
$queueService->stopWorker($workerId);

// Tüm worker'ları yeniden başlat
$queueService->restartWorkers();
```

### Production Setup

```bash
# Supervisor konfigürasyonu
# /etc/supervisor/conf.d/adminkit-queue.conf

[program:adminkit-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/adminkit/vendor/bin/adminkit queue:work --queue=high,default,low
autostart=true
autorestart=true
numprocs=3
redirect_stderr=true
stdout_logfile=/var/log/adminkit-queue.log
stopwaitsecs=3600
```

## 🛡️ Hata Yönetimi

### Başarısız İşler

```php
// Başarısız işi tekrar dene
$queueService->retryJob($jobId);

// Tüm başarısız işleri tekrar dene
$queueService->retryAllFailed();

// Başarısız işi sil
$queueService->deleteFailedJob($jobId);

// Tüm başarısız işleri temizle
$queueService->clearFailedJobs();
```

### Özel Hata İşleme

```php
// İş başarısız olduğunda callback
$queueService->onJobFailed(function($job, $exception) {
    // Hata logla
    error_log("Job failed: {$job['type']} - {$exception->getMessage()}");
    
    // Admin'e bildirim gönder
    $this->notification->send('admin', [
        'title' => 'Job Failed',
        'message' => "Job {$job['id']} failed: {$exception->getMessage()}",
        'type' => 'error'
    ]);
});

// İş başarılı olduğunda callback
$queueService->onJobCompleted(function($job, $result) {
    // İstatistik güncelle
    $this->cache->increment('completed_jobs_today');
});
```

## 🔍 İzleme ve Debugging

### Job Tracking

```php
// İş durumunu takip et
$jobId = $queueService->dispatch('long_running_task', $data);
$status = $queueService->getJobStatus($jobId);

// İş ilerlemesini güncelle
$queueService->updateJobProgress($jobId, [
    'progress' => 75,
    'message' => 'Processing step 3 of 4...'
]);
```

### Performance Monitoring

```php
// Queue performans metrikleri
$metrics = $queueService->getMetrics();
/*
Array(
    'avg_wait_time' => 12.5,     // saniye
    'avg_execution_time' => 45.2, // saniye
    'throughput' => 125,          // jobs/hour
    'success_rate' => 98.5,       // %
    'memory_usage' => '142MB'
)
*/

// Slow query detection
$slowJobs = $queueService->getSlowJobs([
    'threshold' => 300, // 5 dakika
    'limit' => 10
]);
```

## 🎯 Use Cases

### E-ticaret Siparişi

```php
// Sipariş işleme workflow'u
$orderId = 12345;

// 1. Stok kontrolü (critical)
$queueService->dispatch('check_inventory', ['order_id' => $orderId], [
    'queue' => 'critical'
]);

// 2. Ödeme işleme (high priority)
$queueService->dispatch('process_payment', ['order_id' => $orderId], [
    'queue' => 'high',
    'delay' => 30 // stok kontrolünden sonra
]);

// 3. E-posta bildirimi (default)
$queueService->dispatch('order_confirmation_email', ['order_id' => $orderId], [
    'queue' => 'default',
    'delay' => 60
]);

// 4. Kargo hazırlama (low priority)
$queueService->dispatch('prepare_shipping', ['order_id' => $orderId], [
    'queue' => 'low',
    'delay' => 300 // 5 dakika sonra
]);
```

### Blog İçerik Yayınlama

```php
// İçerik workflow'u
$postId = 67890;

// 1. İçerik optimizasyonu
$queueService->dispatch('optimize_content', ['post_id' => $postId], [
    'queue' => 'default'
]);

// 2. SEO analizi
$queueService->dispatch('seo_analysis', ['post_id' => $postId], [
    'queue' => 'default',
    'delay' => 60
]);

// 3. Sosyal medya paylaşımı
$queueService->dispatch('social_media_share', ['post_id' => $postId], [
    'queue' => 'low',
    'delay' => 300
]);

// 4. İstatistik güncelleme
$queueService->schedule('update_post_stats', ['post_id' => $postId], [
    'cron' => '0 * * * *' // Her saat
]);
```

## 💡 Best Practices

### 1. Queue Seçimi
- **Critical**: Sistem kritik işlemler (backup, güvenlik)
- **High**: Kullanıcı deneyimi (e-posta, bildirim)
- **Default**: Standart operasyonlar (export, import)
- **Low**: Temizlik ve bakım işleri

### 2. Timeout Ayarları
```php
// CPU yoğun işlemler için yüksek timeout
$queueService->dispatch('image_processing', $data, [
    'timeout' => 1800 // 30 dakika
]);

// Hızlı işlemler için düşük timeout
$queueService->dispatch('cache_clear', $data, [
    'timeout' => 30 // 30 saniye
]);
```

### 3. Memory Management
```php
// Büyük veri işleme için memory limit
$queueService->dispatch('big_data_processing', $data, [
    'memory_limit' => '1G',
    'queue' => 'low'
]);
```

### 4. Error Handling
```php
// Kritik işlemler için yüksek retry
$queueService->dispatch('payment_processing', $data, [
    'max_attempts' => 5,
    'retry_delay' => 60
]);
```

## 🔗 İlgili Dokümantasyon

- **[Performance Service](performance-service.md)** - Queue performans izleme
- **[Notification Service](notification-service.md)** - Bildirim işleri
- **[Export/Import Service](export-import-service.md)** - Veri işleme işleri

---

AdminKit Queue Service ile ölçeklenebilir ve güvenilir arkaplan işleme sistemi oluşturun.
