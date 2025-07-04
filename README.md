# AdminKit - Modern Turkish Admin Panel

**AdminKit**, modern PHP teknolojileri ile geliştirilmiş, Türkiye'ye özel enterprise düzeyinde admin panel çözümüdür. Laravel/Symfony EasyAdmin'in tüm özelliklerini içerir ve Docker desteği ile kolay kurulum sağlar.

[![Latest Version](https://img.shields.io/packagist/v/turkpin/admin-kit.svg?style=flat-square)](https://packagist.org/packages/turkpin/admin-kit)
[![PHP Version](https://img.shields.io/packagist/php-v/turkpin/admin-kit.svg?style=flat-square)](https://packagist.org/packages/turkpin/admin-kit)
[![License](https://img.shields.io/packagist/l/turkpin/admin-kit.svg?style=flat-square)](LICENSE)
[![Downloads](https://img.shields.io/packagist/dt/turkpin/admin-kit.svg?style=flat-square)](https://packagist.org/packages/turkpin/admin-kit)

## 🚀 Hızlı Başlangıç

### Composer ile Kurulum

```bash
# Package'ı yükle
composer require turkpin/admin-kit

# AdminKit'i kur
php vendor/bin/adminkit install

# Environment dosyasını kopyala
php vendor/bin/adminkit env:copy

# .env dosyasını düzenle (database ayarları)
nano .env

# Veritabanı migration'larını çalıştır
php vendor/bin/adminkit migrate

# Admin kullanıcı oluştur
php vendor/bin/adminkit user:create

# Development server'ı başlat
php vendor/bin/adminkit serve
```

**🎉 AdminKit artık hazır:** http://localhost:8000

### Docker ile Kurulum (Önerilen)

```bash
# Projeyi klonla
git clone https://github.com/turkpin/admin-kit.git
cd admin-kit

# Docker container'ları başlat
php vendor/bin/adminkit docker:up --build --detach

# Otomatik kurulum tamamlandı!
```

**🌐 Servislere Erişim:**
- **AdminKit Panel**: http://localhost:8000
- **MailHog (Email Test)**: http://localhost:8025  
- **Adminer (DB Yönetimi)**: http://localhost:8080

## ✨ Öne Çıkan Özellikler

### 🔧 **Environment Variable Desteği**
- **50+ yapılandırma seçeneği** .env dosyası ile
- **Otomatik tip dönüşümü** (string, bool, int, float)
- **Validation ve hata kontrolü**
- **Laravel-style environment yönetimi**

### 🐳 **Complete Docker Support**
- **Multi-stage Dockerfile** (development/production/nginx)
- **7 servis** ile komple geliştirme ortamı
- **Production-ready** optimizasyonlar
- **One-command deployment**

### 🛡️ **Enterprise Security**
- **Two-Factor Authentication (2FA)** with TOTP
- **Advanced audit logging** ve değişiklik takibi
- **Role-based access control (RBAC)**
- **Session management** with timeout controls

### ⚡ **Performance & Scalability**
- **Background job processing** 4-priority queue system
- **Real-time performance monitoring** ve profiling
- **Multi-layer caching** (File, Redis, Memory)
- **Slow query detection** ve optimization suggestions

### 🌐 **Real-time Features**
- **WebSocket integration** for live updates
- **Server-Sent Events (SSE)** fallback
- **User presence tracking**
- **Real-time notifications** across 5 channels

### 🎨 **Advanced UI/UX**
- **14 comprehensive field types**
- **Dynamic forms** with conditional logic
- **Multi-step wizard forms** with auto-save
- **4 built-in themes** (Light, Dark, Blue, Green)
- **Responsive design** for all devices

### 📊 **Data Management**
- **Advanced filtering** with 16 operators
- **Batch operations** with queue integration
- **Export/Import** in 5 formats (CSV, Excel, JSON, XML, PDF)
- **Global search** across entities
- **Data validation** ve sanitization

### 🌍 **Turkish-First Internationalization**
- **Native Turkish language support**
- **600+ translation keys** in Turkish and English
- **Complete localization system**
- **Turkish date/time formatting**

## 📋 CLI Komutları

### 🏗️ **Installation & Management**
```bash
php vendor/bin/adminkit install         # AdminKit kurulumu
php vendor/bin/adminkit update          # Package güncelleme
php vendor/bin/adminkit version         # Versiyon bilgisi
```

### 🔧 **Environment Management**
```bash
php vendor/bin/adminkit env:copy        # .env.example → .env
php vendor/bin/adminkit env:check       # Environment doğrulama
```

### 🐳 **Docker Management**
```bash
php vendor/bin/adminkit docker:up       # Container'ları başlat
php vendor/bin/adminkit docker:up -d    # Detached mode
php vendor/bin/adminkit docker:down     # Container'ları durdur
php vendor/bin/adminkit docker:down -v  # Volume'lar ile birlikte
```

### 👥 **User Management**
```bash
php vendor/bin/adminkit user:create                    # Interactive user creation
php vendor/bin/adminkit user:create "Admin" admin@test.com  # Quick user creation
```

### 🗄️ **Database Operations**
```bash
php vendor/bin/adminkit migrate         # Migration'ları çalıştır
php vendor/bin/adminkit migrate --rollback  # Son migration'ı geri al
```

### 🚀 **Development & Deployment**
```bash
php vendor/bin/adminkit serve           # Development server
php vendor/bin/adminkit serve -p 9000   # Custom port
php vendor/bin/adminkit queue:work      # Queue worker
php vendor/bin/adminkit cache:clear     # Cache temizle
```

### 📦 **Asset Management**
```bash
php vendor/bin/adminkit publish:assets     # Asset'leri yayınla
php vendor/bin/adminkit publish:templates  # Template'leri yayınla
```

## 🔧 Environment Konfigürasyonu

AdminKit, **50+ environment variable** ile komple konfigürasyon desteği sunar:

### 📝 **Temel Ayarlar**
```env
APP_NAME="AdminKit Panel"
APP_URL=http://localhost:8000
APP_DEBUG=true
APP_TIMEZONE=Europe/Istanbul
APP_LOCALE=tr
```

### 🗄️ **Veritabanı**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=adminkit
DB_USERNAME=root
DB_PASSWORD=
```

### 🔐 **Authentication**
```env
AUTH_2FA_ENABLED=true
AUTH_SESSION_TIMEOUT=7200
AUTH_PASSWORD_MIN_LENGTH=8
AUTH_MAX_LOGIN_ATTEMPTS=5
```

### ⚡ **Cache & Performance**
```env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
QUEUE_CONNECTION=redis
```

### 📧 **Mail Configuration**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
```

**📚 Tüm environment seçenekleri için:** [.env.example](.env.example) dosyasına bakın.

## 🏗️ Teknoloji Stack

- **PHP 8.1+** - Modern PHP özellikleri
- **Doctrine ORM** - Enterprise veritabanı yönetimi
- **Smarty Templates** - Güvenli template engine
- **Tailwind CSS** - Modern CSS framework
- **Redis** - High-performance caching
- **WebSocket** - Real-time communication
- **Docker** - Containerized deployment

## 📖 Dokümantasyon

- **[Kurulum Rehberi](docs/installation.md)** - Detaylı kurulum adımları
- **[Hızlı Başlangıç](docs/quick-start.md)** - 5 dakikada AdminKit
- **[Field Types](docs/field-types.md)** - 14 field type dokümantasyonu
- **[Services](docs/services/README.md)** - Service dokümantasyonu
- **[Advanced](docs/advanced/)** - İleri seviye konular
- **[Examples](docs/examples/)** - Örnek uygulamalar

## 🤝 Geliştirme ve Katkı

```bash
# Development ortamını hazırla
git clone https://github.com/turkpin/admin-kit.git
cd admin-kit
composer install

# Docker ile development
php vendor/bin/adminkit docker:up --build

# Test'leri çalıştır
composer test

# Code style check
composer cs-check
```

**Katkı rehberi:** [CONTRIBUTING.md](CONTRIBUTING.md)

## 📈 Production Deployment

### 🐳 **Docker Production**
```bash
# Production image build
docker build --target production -t adminkit:latest .

# Production deployment
docker-compose -f docker-compose.prod.yml up -d
```

### 🚀 **Manuel Deployment**
```bash
# Dependencies (production only)
composer install --no-dev --optimize-autoloader

# Environment setup
cp .env.example .env
# Configure production values

# Database migration
php vendor/bin/adminkit migrate

# Asset optimization
php vendor/bin/adminkit publish:assets

# Cache optimization
php vendor/bin/adminkit cache:warm
```

## 🔧 Sistem Gereksinimleri

- **PHP**: 8.1 veya üzeri
- **Extensions**: PDO, mbstring, intl, gd, zip, redis (optional)
- **Database**: MySQL 8.0+ veya PostgreSQL 13+
- **Memory**: Minimum 256MB, Önerilen 512MB+
- **Disk**: Minimum 100MB

## 🆚 EasyAdmin Karşılaştırması

| Özellik | AdminKit | EasyAdmin |
|---------|----------|-----------|
| **Turkish Support** | ✅ Native | ❌ Limited |
| **Docker Support** | ✅ Complete | ❌ Manual |
| **Environment Variables** | ✅ 50+ options | ❌ Basic |
| **Real-time Features** | ✅ WebSocket | ❌ None |
| **CLI Tools** | ✅ 15+ commands | ❌ Basic |
| **2FA Support** | ✅ TOTP | ❌ None |
| **Queue System** | ✅ 4-priority | ❌ None |
| **Performance Monitoring** | ✅ Built-in | ❌ None |

## 🎯 Use Cases

- **E-commerce Admin Panels**
- **CRM Systems**
- **Content Management Systems**
- **Inventory Management**
- **User Management Systems**
- **Reporting Dashboards**
- **API Management Panels**

## 📞 Destek ve İletişim

- **GitHub Issues**: [Sorun bildir](https://github.com/turkpin/admin-kit/issues)
- **Discussions**: [Topluluk tartışmaları](https://github.com/turkpin/admin-kit/discussions)
- **Email**: [admin-kit@turkpin.com](mailto:admin-kit@turkpin.com)

## 📄 Lisans

AdminKit, [MIT lisansı](LICENSE) altında açık kaynak olarak sunulmaktadır.

## 🏆 Özellikler

- ✅ **Production Ready** - Enterprise düzeyinde kararlılık
- ✅ **Turkish-First** - Türkiye odaklı geliştirme
- ✅ **Docker Support** - Modern deployment
- ✅ **Environment Management** - Professional konfigürasyon
- ✅ **CLI Tools** - Geliştirici dostu araçlar
- ✅ **Real-time Features** - Modern web uygulaması
- ✅ **Security** - Enterprise güvenlik
- ✅ **Performance** - Yüksek performans optimizasyonu

---

**AdminKit ile admin panellerinizi bir sonraki seviyeye taşıyın!** 🚀

*Modern, güvenli, performanslı ve Türkiye'ye özel admin panel çözümü.*
