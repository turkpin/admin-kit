# AdminKit - Modern Turkish Admin Panel

**AdminKit**, modern PHP teknolojileri ile geliştirilmiş, Türkiye'ye özel enterprise düzeyinde admin panel çözümüdür. Laravel/Symfony EasyAdmin'in tüm özelliklerini içerir ve **tek komutla complete Docker setup** sağlar.

[![Latest Version](https://img.shields.io/packagist/v/turkpin/admin-kit.svg?style=flat-square)](https://packagist.org/packages/turkpin/admin-kit)
[![PHP Version](https://img.shields.io/packagist/php-v/turkpin/admin-kit.svg?style=flat-square)](https://packagist.org/packages/turkpin/admin-kit)
[![License](https://img.shields.io/packagist/l/turkpin/admin-kit.svg?style=flat-square)](LICENSE)
[![Downloads](https://img.shields.io/packagist/dt/turkpin/admin-kit.svg?style=flat-square)](https://packagist.org/packages/turkpin/admin-kit)

## 🚀 Ultra Hızlı Başlangıç (v1.0.5)

### ⚡ Tek Komut ile Complete Setup

```bash
# Package'ı yükle ve kur
composer require turkpin/admin-kit
php vendor/bin/adminkit install

# ✅ .env otomatik oluşturulur
# ✅ Docker dosyaları otomatik kopyalanır (isteğe bağlı)
# ✅ Tüm konfigürasyon otomatik yapılır
# ✅ Migrate'e hazır duruma gelir

# Docker seçtiyseniz direkt başlatın:
docker-compose up --build -d
docker-compose exec app php vendor/bin/adminkit migrate
docker-compose exec app php vendor/bin/adminkit user:create
```

**🎉 AdminKit artık hazır:** http://localhost:8000

### 🎯 Ne Değişti v1.0.5'te?

#### **Öncesi (Manual Steps):**
```bash
composer require turkpin/admin-kit
php vendor/bin/adminkit install
php vendor/bin/adminkit env:copy           # Manuel adım
cp vendor/.../docker/example/* .          # Manuel kopyalama
nano .env                                  # Manuel Docker config
```

#### **Sonrası (Automatic Magic):**
```bash
composer require turkpin/admin-kit
php vendor/bin/adminkit install           # Interactive Docker setup
# "Do you want Docker?" → Yes
# ✅ Herşey otomatik!
docker-compose up --build -d             # Direkt çalışır
```

### 🐳 Smart Docker Integration

Install sırasında AdminKit size sorar:
- **"Do you want to include Docker files for easy setup?"**
  - **Yes**: Docker dosyları kopyalanır, .env Docker için konfigüre edilir
  - **No**: Local development için standard setup

**Docker seçerseniz otomatik konfigürasyon:**
- `DB_HOST=mysql` (Docker service)
- `REDIS_HOST=redis` (Docker service)  
- `MAIL_HOST=mailhog` (Email testing)
- `CACHE_DRIVER=redis` (Performance)

## ✨ Öne Çıkan Özellikler

### 🔧 **Intelligent Environment Management**
- **Otomatik .env oluşturma** install sırasında
- **Docker-aware konfigürasyon** seçime göre
- **50+ environment variable** desteği
- **Laravel-style environment yönetimi**

### 🐳 **Revolutionary Docker Support**
- **Interactive setup** tek soru ile
- **Automatic file publishing** gerekli Docker dosyaları
- **Smart configuration** Docker services için otomatik ayar
- **One-command deployment** production ready

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

## 📋 CLI Komutları (v1.0.5)

### 🏗️ **Intelligent Installation**
```bash
php vendor/bin/adminkit install         # Interactive Docker setup
php vendor/bin/adminkit install --with-docker    # Force Docker files
php vendor/bin/adminkit install --skip-docker    # Skip Docker completely
php vendor/bin/adminkit update          # Package güncelleme
```

### 🔧 **Environment Management**
```bash
# Artık manual env:copy gerekmiyor!
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

## 🔧 Smart Environment Configuration

AdminKit v1.0.5, **otomatik environment detection** ile Docker kurulumu algılar:

### 📝 **Local Development (Default)**
```env
APP_NAME="AdminKit Panel"
APP_URL=http://localhost:8000
DB_HOST=127.0.0.1
CACHE_DRIVER=file
QUEUE_CONNECTION=database
MAIL_HOST=smtp.gmail.com
```

### 🐳 **Docker Setup (Auto-configured)**
```env
APP_NAME="AdminKit Panel"
APP_URL=http://localhost:8000
DB_HOST=mysql                # Docker service
DB_USERNAME=adminkit         # Auto-configured
DB_PASSWORD=adminkit123      # Auto-configured
CACHE_DRIVER=redis           # Performance
REDIS_HOST=redis             # Docker service
QUEUE_CONNECTION=redis       # Performance
MAIL_HOST=mailhog            # Email testing
MAIL_PORT=1025               # MailHog port
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
- **[Docker Example](docker/example/README.md)** - Package Docker kurulumu
- **[Hızlı Başlangıç](docs/quick-start.md)** - 5 dakikada AdminKit
- **[Field Types](docs/field-types.md)** - 14 field type dokümantasyonu
- **[Services](docs/services/README.md)** - Service dokümantasyonu

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
```

**Katkı rehberi:** [CONTRIBUTING.md](CONTRIBUTING.md)

## 📈 Production Deployment

### 🐳 **Docker Production (Recommended)**
```bash
# Tek komutla production setup
composer require turkpin/admin-kit
php vendor/bin/adminkit install --with-docker

# Production deployment
docker-compose -f docker-compose.prod.yml up -d
```

### 🚀 **Manuel Deployment**
```bash
# Dependencies (production only)
composer install --no-dev --optimize-autoloader

# AdminKit kurulum
php vendor/bin/adminkit install

# Database migration
php vendor/bin/adminkit migrate

# Asset optimization
php vendor/bin/adminkit publish:assets
```

## 🔧 Sistem Gereksinimleri

- **PHP**: 8.1 veya üzeri
- **Extensions**: PDO, mbstring, intl, gd, zip, redis (optional)
- **Database**: MySQL 8.0+ veya PostgreSQL 13+
- **Memory**: Minimum 256MB, Önerilen 512MB+
- **Disk**: Minimum 100MB

## 🆚 EasyAdmin Karşılaştırması

| Özellik | AdminKit v1.0.5 | EasyAdmin |
|---------|------------------|-----------|
| **Turkish Support** | ✅ Native | ❌ Limited |
| **One-Command Setup** | ✅ Complete | ❌ Manual |
| **Smart Docker Integration** | ✅ Interactive | ❌ Manual |
| **Auto Environment Setup** | ✅ Intelligent | ❌ Manual |
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

## 🏆 v1.0.5 Özellikleri

- ✅ **Intelligent Installation** - Smart Docker setup with interactive prompts
- ✅ **Auto Environment Setup** - Zero manual configuration steps
- ✅ **One-Command Deployment** - Complete setup in seconds
- ✅ **Smart Docker Integration** - Auto-detects and configures for Docker
- ✅ **Migration-Ready State** - Instantly ready for database setup
- ✅ **User Experience Revolution** - Eliminated all manual steps
- ✅ **Production Ready** - Enterprise düzeyinde kararlılık
- ✅ **Turkish-First** - Türkiye odaklı geliştirme

---

**AdminKit v1.0.5 ile admin panellerinizi saniyeler içinde kurabilirsiniz!** 🚀

*Modern, intelligent, zero-configuration admin panel solution.*

## 🔄 Version History

- **v1.0.5**: Intelligent installation with auto Docker setup
- **v1.0.4**: Complete environment variable system
- **v1.0.3**: Full CLI command suite
- **v1.0.2**: PHP 8+ compatibility fixes
- **v1.0.1**: Initial Packagist release
- **v1.0.0**: Full feature release
