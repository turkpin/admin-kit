# AdminKit Kurulum Rehberi

Bu rehber, AdminKit'in farklı ortamlarda kurulumu için detaylı adımları içerir.

## 📋 Sistem Gereksinimleri

### Minimum Gereksinimler
- **PHP**: 8.1 veya üzeri
- **Memory**: 256MB RAM
- **Disk**: 100MB boş alan
- **Database**: MySQL 5.7+ veya PostgreSQL 12+

### Önerilen Gereksinimler  
- **PHP**: 8.2 veya üzeri
- **Memory**: 512MB+ RAM
- **Disk**: 500MB+ boş alan
- **Database**: MySQL 8.0+ veya PostgreSQL 14+

### Gerekli PHP Extensions
```bash
# Zorunlu extensions
php-pdo
php-mbstring
php-intl
php-gd
php-zip
php-xml
php-curl
php-json

# Önerilen extensions
php-redis        # Cache ve queue için
php-opcache      # Performance için
php-xdebug       # Development için
```

## 🚀 Hızlı Kurulum (Önerilen)

### 1. Composer ile Package Kurulumu

```bash
# Yeni proje oluştur
mkdir my-admin-panel
cd my-admin-panel

# AdminKit'i yükle
composer require turkpin/admin-kit

# AdminKit'i kur
php vendor/bin/adminkit install
```

### 2. Environment Konfigürasyonu

```bash
# Environment dosyasını kopyala
php vendor/bin/adminkit env:copy

# .env dosyasını düzenle
nano .env
```

**.env dosyasında minimum gerekli ayarlar:**
```env
APP_NAME="My Admin Panel"
APP_URL=http://localhost:8000

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=my_admin_db
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 3. Veritabanı Kurulumu

```bash
# Environment'ı kontrol et
php vendor/bin/adminkit env:check

# Migration'ları çalıştır
php vendor/bin/adminkit migrate

# Admin kullanıcı oluştur
php vendor/bin/adminkit user:create
```

### 4. Geliştirme Sunucusunu Başlat

```bash
# Built-in server ile başlat
php vendor/bin/adminkit serve

# Custom port ile
php vendor/bin/adminkit serve -p 9000
```

**🎉 Kurulum Tamamlandı!** AdminKit artık http://localhost:8000 adresinde çalışıyor.

## 🐳 Docker ile Kurulum (Tam Otomatik)

Docker ile kurulum, tüm dependencies ve servisleri otomatik olarak kurar.

### 1. AdminKit Repository'yi Klonla

```bash
git clone https://github.com/turkpin/admin-kit.git
cd admin-kit
```

### 2. Docker Container'ları Başlat

```bash
# Container'ları build et ve başlat
php vendor/bin/adminkit docker:up --build --detach

# İlk kurulum komutlarını çalıştır (opsiyonel)
docker-compose exec app php vendor/bin/adminkit migrate
docker-compose exec app php vendor/bin/adminkit user:create
```

### 3. Servislere Erişim

| Servis | URL | Açıklama |
|--------|-----|----------|
| **AdminKit Panel** | http://localhost:8000 | Ana admin panel |
| **MailHog** | http://localhost:8025 | Email test aracı |
| **Adminer** | http://localhost:8080 | Database yönetimi |
| **Redis** | localhost:6379 | Cache ve queue |
| **MySQL** | localhost:3306 | Database |

### Docker Komutları

```bash
# Container'ları durdur
php vendor/bin/adminkit docker:down

# Volume'lar ile birlikte durdur
php vendor/bin/adminkit docker:down -v

# Logları görüntüle
docker-compose logs -f app

# Container içine gir
docker-compose exec app bash
```

## 🔧 Manuel Kurulum (Advanced)

Manuel kurulum, tam kontrol isteyenler için adım adım rehberdir.

### 1. Proje Klasörü Oluştur

```bash
mkdir my-admin-panel
cd my-admin-panel
```

### 2. Composer Kurulumu

```bash
# composer.json oluştur
composer init

# AdminKit'i yükle
composer require turkpin/admin-kit

# Development dependencies (opsiyonel)
composer require --dev phpunit/phpunit
composer require --dev squizlabs/php_codesniffer
```

### 3. Dizin Yapısını Oluştur

```bash
# Gerekli dizinleri oluştur
mkdir -p public/{assets,uploads}
mkdir -p config
mkdir -p templates
mkdir -p migrations
mkdir -p cache
mkdir -p logs

# Permission'ları ayarla
chmod 755 public
chmod 777 cache logs public/uploads
```

### 4. Web Server Konfigürasyonu

#### Nginx Konfigürasyonu

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path/to/your/project/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    # AdminKit assets
    location /assets/adminkit/ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
    add_header X-Content-Type-Options "nosniff";
}
```

#### Apache Konfigürasyonu

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /path/to/your/project/public
    
    <Directory /path/to/your/project/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    # AdminKit assets caching
    <LocationMatch "\.(css|js|png|jpg|jpeg|gif|ico|svg)$">
        ExpiresActive On
        ExpiresDefault "access plus 1 year"
    </LocationMatch>
</VirtualHost>
```

### 5. Environment Ayarları

```bash
# .env dosyasını oluştur
cp .env.example .env

# APP_KEY oluştur (gelecek versiyonda)
php vendor/bin/adminkit key:generate
```

### 6. Database Kurulumu

```bash
# MySQL database oluştur
mysql -u root -p
CREATE DATABASE my_admin_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'adminkit'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON my_admin_db.* TO 'adminkit'@'localhost';
FLUSH PRIVILEGES;
exit;

# Migration'ları çalıştır
php vendor/bin/adminkit migrate
```

## 🔒 Production Kurulumu

Production ortamı için güvenlik ve performans optimizasyonları.

### 1. Dependencies Optimization

```bash
# Production dependencies
composer install --no-dev --optimize-autoloader

# Asset optimization
php vendor/bin/adminkit publish:assets
```

### 2. Environment Configuration

```env
# Production .env ayarları
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database
DB_HOST=your-db-host
DB_DATABASE=production_db
DB_USERNAME=secure_user
DB_PASSWORD=very_secure_password

# Cache
CACHE_DRIVER=redis
REDIS_HOST=your-redis-host

# Mail
MAIL_MAILER=smtp
MAIL_HOST=your-mail-host
MAIL_USERNAME=your-mail-user
MAIL_PASSWORD=your-mail-password

# Security
SECURITY_HTTPS_ONLY=true
SECURITY_CSRF_ENABLED=true
SESSION_SECURE=true
```

### 3. Web Server Optimizations

#### Nginx Production Config

```nginx
server {
    listen 443 ssl http2;
    server_name yourdomain.com;
    root /var/www/adminkit/public;

    # SSL Configuration
    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;
    ssl_protocols TLSv1.2 TLSv1.3;

    # Security Headers
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Gzip Compression
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/javascript;

    # Rate Limiting
    limit_req_zone $binary_remote_addr zone=adminkit:10m rate=10r/s;
    limit_req zone=adminkit burst=20 nodelay;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
    }
}
```

### 4. Process Management

```bash
# Systemd service for queue worker
sudo nano /etc/systemd/system/adminkit-queue.service
```

```ini
[Unit]
Description=AdminKit Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/adminkit
ExecStart=/usr/bin/php /var/www/adminkit/vendor/bin/adminkit queue:work --timeout=3600
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
```

```bash
# Service'i aktifleştir
sudo systemctl enable adminkit-queue
sudo systemctl start adminkit-queue
```

## 🚨 Sorun Giderme

### Yaygın Sorunlar ve Çözümleri

#### 1. Permission Errors

```bash
# Permission'ları düzelt
sudo chown -R www-data:www-data /path/to/adminkit
sudo chmod -R 755 /path/to/adminkit/public
sudo chmod -R 777 /path/to/adminkit/cache
sudo chmod -R 777 /path/to/adminkit/logs
sudo chmod -R 777 /path/to/adminkit/public/uploads
```

#### 2. Database Connection Errors

```bash
# Connection'ı test et
php vendor/bin/adminkit env:check

# Manual connection test
php -r "
try {
    \$pdo = new PDO('mysql:host=127.0.0.1;dbname=test', 'root', '');
    echo 'Connection successful!';
} catch (Exception \$e) {
    echo 'Connection failed: ' . \$e->getMessage();
}
"
```

#### 3. Missing PHP Extensions

```bash
# Ubuntu/Debian
sudo apt-get install php8.1-pdo php8.1-mbstring php8.1-intl php8.1-gd php8.1-zip

# CentOS/RHEL
sudo yum install php81-php-pdo php81-php-mbstring php81-php-intl php81-php-gd php81-php-zip

# macOS (Homebrew)
brew install php@8.1
```

#### 4. Memory Limit Issues

```bash
# php.ini'de memory limit'i artır
memory_limit = 512M

# Veya runtime'da
php -d memory_limit=512M vendor/bin/adminkit install
```

### Debug Modu

```bash
# Debug modu aktif et
echo "APP_DEBUG=true" >> .env

# Log dosyalarını kontrol et
tail -f logs/adminkit.log

# CLI debug
php vendor/bin/adminkit env:check --verbose
```

## 📋 Kurulum Sonrası Kontrol Listesi

- [ ] **Environment Variables**: .env dosyası doğru yapılandırıldı
- [ ] **Database Connection**: Database bağlantısı çalışıyor
- [ ] **Migrations**: Tüm migration'lar çalıştırıldı
- [ ] **Admin User**: En az bir admin kullanıcı oluşturuldu
- [ ] **File Permissions**: Gerekli dizinler yazılabilir
- [ ] **Web Server**: Nginx/Apache doğru yapılandırıldı
- [ ] **SSL Certificate**: HTTPS aktif (production için)
- [ ] **Cache**: Redis/file cache çalışıyor
- [ ] **Queue Worker**: Background job processing aktif
- [ ] **Email**: Mail ayarları test edildi
- [ ] **Backup**: Backup stratejisi belirlendi

## 🔄 Güncelleme

```bash
# Package'ı güncelle
composer update turkpin/admin-kit

# AdminKit dosyalarını güncelle
php vendor/bin/adminkit update

# Yeni migration'ları çalıştır
php vendor/bin/adminkit migrate

# Cache'i temizle
php vendor/bin/adminkit cache:clear
```

## 🆘 Yardım ve Destek

### Dokümantasyon
- **[Hızlı Başlangıç](quick-start.md)** - 5 dakikada AdminKit
- **[CLI Komutları](../README.md#cli-komutları)** - Tüm CLI komutları
- **[Environment Variables](../README.md#environment-konfigürasyonu)** - Konfigürasyon seçenekleri

### Topluluk Desteği
- **GitHub Issues**: [Sorun bildir](https://github.com/turkpin/admin-kit/issues)
- **GitHub Discussions**: [Topluluk](https://github.com/turkpin/admin-kit/discussions)
- **Email**: admin-kit@turkpin.com

### Professional Support
Enterprise düzeyinde destek için [iletişime geçin](mailto:admin-kit@turkpin.com).

---

**AdminKit kurulumu ile ilgili sorun yaşıyorsanız, yukarıdaki sorun giderme bölümünü kontrol edin veya GitHub'da issue açın.**
