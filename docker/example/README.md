# AdminKit Package Docker Example

Bu dizin, AdminKit'i Composer package olarak yükledikten sonra Docker ile çalıştırmak için gerekli dosyaları içerir.

## 🚀 Hızlı Kurulum

### 1. Yeni Projenizi Oluşturun

```bash
# Yeni bir dizin oluşturun
mkdir my-admin-panel
cd my-admin-panel

# Composer.json dosyasını kopyalayın
cp /path/to/adminkit/docker/example/composer.json .

# AdminKit'i yükleyin
composer install
```

### 2. Docker Dosyalarını Kopyalayın

```bash
# Docker dosyalarını projenize kopyalayın
cp -r /path/to/adminkit/docker/example/* .

# Veya AdminKit package'ından kopyalayın:
cp vendor/turkpin/admin-kit/docker/example/Dockerfile .
cp vendor/turkpin/admin-kit/docker/example/docker-compose.yml .
cp -r vendor/turkpin/admin-kit/docker/example/docker .
```

### 3. AdminKit'i Kurun

```bash
# AdminKit'i install edin
php vendor/bin/adminkit install

# Environment dosyasını oluşturun
php vendor/bin/adminkit env:copy

# .env dosyasını Docker için düzenleyin
nano .env
```

**.env dosyasında Docker ayarları:**
```env
# Database (Docker services)
DB_HOST=mysql
DB_DATABASE=adminkit
DB_USERNAME=adminkit
DB_PASSWORD=adminkit123

# Cache
CACHE_DRIVER=redis
REDIS_HOST=redis

# Queue
QUEUE_CONNECTION=redis

# Mail (MailHog test için)
MAIL_HOST=mailhog
MAIL_PORT=1025
```

### 4. Docker Container'ları Başlatın

```bash
# Container'ları build edin ve başlatın
docker-compose up --build -d

# İlk kurulum komutlarını çalıştırın
docker-compose exec app php vendor/bin/adminkit migrate
docker-compose exec app php vendor/bin/adminkit user:create
```

## 🌐 Servislere Erişim

| Servis | URL | Kullanıcı/Şifre |
|--------|-----|------------------|
| **AdminKit Panel** | http://localhost:8000 | Oluşturduğunuz admin kullanıcı |
| **MailHog (Email Test)** | http://localhost:8025 | - |
| **Adminer (DB Yönetimi)** | http://localhost:8080 | adminkit / adminkit123 |

## 📁 Proje Yapısı

```
my-admin-panel/
├── composer.json          # AdminKit dependency
├── Dockerfile             # PHP container
├── docker-compose.yml     # Tüm servisler
├── docker/
│   ├── nginx.conf         # Nginx konfigürasyonu
│   └── php.ini            # PHP ayarları
├── .env                   # Environment variables
├── public/                # Web root (AdminKit install sonrası)
├── config/                # AdminKit config
├── migrations/            # Database migrations
├── cache/                 # Cache files
├── logs/                  # Log files
└── vendor/                # Composer dependencies
```

## 🔧 Yaygın Komutlar

```bash
# Container'ları başlat
docker-compose up -d

# Container'ları durdur
docker-compose down

# Logları görüntüle
docker-compose logs -f app

# Container içine gir
docker-compose exec app sh

# AdminKit komutları çalıştır
docker-compose exec app php vendor/bin/adminkit migrate
docker-compose exec app php vendor/bin/adminkit user:create
docker-compose exec app php vendor/bin/adminkit cache:clear

# Database'e bağlan
docker-compose exec mysql mysql -u adminkit -padminkit123 adminkit
```

## 🔄 Güncelleme

```bash
# AdminKit'i güncelle
composer update turkpin/admin-kit

# AdminKit dosyalarını güncelle
docker-compose exec app php vendor/bin/adminkit update

# Yeni migration'ları çalıştır
docker-compose exec app php vendor/bin/adminkit migrate

# Container'ları yeniden başlat
docker-compose restart app
```

## 🚨 Sorun Giderme

### Permission Hatası
```bash
# Permission'ları düzelt
docker-compose exec app chown -R www-data:www-data /var/www/html
docker-compose exec app chmod -R 777 /var/www/html/cache
docker-compose exec app chmod -R 777 /var/www/html/logs
```

### Database Bağlantı Hatası
```bash
# Database'in hazır olmasını bekleyin
docker-compose logs mysql

# Environment'ı kontrol edin
docker-compose exec app php vendor/bin/adminkit env:check
```

### Container Build Hatası
```bash
# Cache'i temizleyip yeniden build edin
docker-compose down
docker system prune -f
docker-compose up --build
```

## 📦 Production Deployment

Production için ayrı bir docker-compose.prod.yml dosyası oluşturun:

```yaml
version: '3.8'
services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
      target: production
    environment:
      - APP_DEBUG=false
      - CACHE_DRIVER=redis
    # ... diğer production ayarları
```

```bash
# Production deployment
docker-compose -f docker-compose.prod.yml up -d
```

## 🆘 Destek

- **AdminKit Dokümantasyonu**: [GitHub](https://github.com/turkpin/admin-kit)
- **Docker Issues**: Docker ile ilgili sorunlar için GitHub Issues
- **AdminKit CLI**: `docker-compose exec app php vendor/bin/adminkit --help`

---

**AdminKit Package'ını Docker ile başarıyla çalıştırıyorsunuz!** 🎉
