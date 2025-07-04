# AdminKit - Modern Admin Panel Solution

**AdminKit** is an enterprise-grade admin panel solution built with modern PHP technologies. It provides complete EasyAdmin feature parity with **intelligent one-command Docker setup** and smart environment management.

[![Latest Version](https://img.shields.io/packagist/v/turkpin/admin-kit.svg?style=flat-square)](https://packagist.org/packages/turkpin/admin-kit)
[![PHP Version](https://img.shields.io/packagist/php-v/turkpin/admin-kit.svg?style=flat-square)](https://packagist.org/packages/turkpin/admin-kit)
[![License](https://img.shields.io/packagist/l/turkpin/admin-kit.svg?style=flat-square)](LICENSE)
[![Downloads](https://img.shields.io/packagist/dt/turkpin/admin-kit.svg?style=flat-square)](https://packagist.org/packages/turkpin/admin-kit)

## 🚀 Ultra Fast Setup (v1.0.5)

### ⚡ Complete Setup in One Command

```bash
# Install and setup the package
composer require turkpin/admin-kit
php vendor/bin/adminkit install

# ✅ .env automatically created
# ✅ Docker files automatically published (optional)
# ✅ Complete configuration done automatically
# ✅ Migration-ready state achieved

# If you chose Docker, start immediately:
docker-compose up --build -d
docker-compose exec app php vendor/bin/adminkit migrate
docker-compose exec app php vendor/bin/adminkit user:create
```

**🎉 AdminKit is ready:** http://localhost:8000

### 🎯 What Changed in v1.0.5?

#### **Before (Manual Steps):**
```bash
composer require turkpin/admin-kit
php vendor/bin/adminkit install
php vendor/bin/adminkit env:copy           # Manual step
cp vendor/.../docker/example/* .          # Manual copy
nano .env                                  # Manual Docker config
```

#### **After (Automatic Magic):**
```bash
composer require turkpin/admin-kit
php vendor/bin/adminkit install           # Interactive Docker setup
# "Do you want Docker?" → Yes
# ✅ Everything automatic!
docker-compose up --build -d             # Works directly
```

### 🐳 Smart Docker Integration

During installation, AdminKit asks:
- **"Do you want to include Docker files for easy setup?"**
  - **Yes**: Docker files copied, .env configured for Docker
  - **No**: Standard setup for local development

**Docker selection provides automatic configuration:**
- `DB_HOST=mysql` (Docker service)
- `REDIS_HOST=redis` (Docker service)  
- `MAIL_HOST=mailhog` (Email testing)
- `CACHE_DRIVER=redis` (Performance)

## ✨ Key Features

### 🔧 **Intelligent Environment Management**
- **Automatic .env creation** during installation
- **Docker-aware configuration** based on selection
- **50+ environment variables** support
- **Laravel-style environment management**

### 🐳 **Revolutionary Docker Support**
- **Interactive setup** with single question
- **Automatic file publishing** for required Docker files
- **Smart configuration** automatic setup for Docker services
- **One-command deployment** production ready

### 🛡️ **Enterprise Security**
- **Two-Factor Authentication (2FA)** with TOTP
- **Advanced audit logging** and change tracking
- **Role-based access control (RBAC)**
- **Session management** with timeout controls

### ⚡ **Performance & Scalability**
- **Background job processing** 4-priority queue system
- **Real-time performance monitoring** and profiling
- **Multi-layer caching** (File, Redis, Memory)
- **Slow query detection** and optimization suggestions

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
- **Data validation** and sanitization

### 🌍 **Internationalization**
- **Multi-language support** with complete i18n system
- **Built-in translations** for English and Turkish
- **600+ translation keys** for comprehensive coverage
- **Extensible localization** system

## 📋 CLI Commands (v1.0.5)

### 🏗️ **Intelligent Installation**
```bash
php vendor/bin/adminkit install         # Interactive Docker setup
php vendor/bin/adminkit install --with-docker    # Force Docker files
php vendor/bin/adminkit install --skip-docker    # Skip Docker completely
php vendor/bin/adminkit update          # Package update
```

### 🔧 **Environment Management**
```bash
# No more manual env:copy needed!
php vendor/bin/adminkit env:check       # Environment validation
```

### 🐳 **Docker Management**
```bash
php vendor/bin/adminkit docker:up       # Start containers
php vendor/bin/adminkit docker:up -d    # Detached mode
php vendor/bin/adminkit docker:down     # Stop containers
php vendor/bin/adminkit docker:down -v  # With volumes
```

### 👥 **User Management**
```bash
php vendor/bin/adminkit user:create                    # Interactive user creation
php vendor/bin/adminkit user:create "Admin" admin@test.com  # Quick user creation
```

### 🗄️ **Database Operations**
```bash
php vendor/bin/adminkit migrate         # Run migrations
php vendor/bin/adminkit migrate --rollback  # Rollback last migration
```

### 🚀 **Development & Deployment**
```bash
php vendor/bin/adminkit serve           # Development server
php vendor/bin/adminkit serve -p 9000   # Custom port
php vendor/bin/adminkit queue:work      # Queue worker
php vendor/bin/adminkit cache:clear     # Clear cache
```

## 🔧 Smart Environment Configuration

AdminKit v1.0.5 features **automatic environment detection** for Docker setup:

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

**📚 For all environment options:** See [.env.example](.env.example) file.

## 🏗️ Technology Stack

- **PHP 8.1+** - Modern PHP features
- **Doctrine ORM** - Enterprise database management
- **Smarty Templates** - Secure template engine
- **Tailwind CSS** - Modern CSS framework
- **Redis** - High-performance caching
- **WebSocket** - Real-time communication
- **Docker** - Containerized deployment

## 📖 Documentation

- **[Installation Guide](docs/installation.md)** - Detailed installation steps
- **[Docker Example](docker/example/README.md)** - Package Docker setup
- **[Quick Start](docs/quick-start.md)** - 5-minute AdminKit
- **[Field Types](docs/field-types.md)** - 14 field type documentation
- **[Services](docs/services/README.md)** - Service documentation

## 🤝 Development and Contributing

```bash
# Setup development environment
git clone https://github.com/turkpin/admin-kit.git
cd admin-kit
composer install

# Docker development
php vendor/bin/adminkit docker:up --build

# Run tests
composer test
```

**Contributing guide:** [CONTRIBUTING.md](CONTRIBUTING.md)

## 📈 Production Deployment

### 🐳 **Docker Production (Recommended)**
```bash
# One-command production setup
composer require turkpin/admin-kit
php vendor/bin/adminkit install --with-docker

# Production deployment
docker-compose -f docker-compose.prod.yml up -d
```

### 🚀 **Manual Deployment**
```bash
# Dependencies (production only)
composer install --no-dev --optimize-autoloader

# AdminKit installation
php vendor/bin/adminkit install

# Database migration
php vendor/bin/adminkit migrate

# Asset optimization
php vendor/bin/adminkit publish:assets
```

## 🔧 System Requirements

- **PHP**: 8.1 or higher
- **Extensions**: PDO, mbstring, intl, gd, zip, redis (optional)
- **Database**: MySQL 8.0+ or PostgreSQL 13+
- **Memory**: Minimum 256MB, Recommended 512MB+
- **Disk**: Minimum 100MB

## 🆚 Comparison with EasyAdmin

| Feature | AdminKit v1.0.5 | EasyAdmin |
|---------|------------------|-----------|
| **Multi-language Support** | ✅ Complete | ❌ Limited |
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

## 📞 Support and Contact

- **GitHub Issues**: [Report an issue](https://github.com/turkpin/admin-kit/issues)
- **Discussions**: [Community discussions](https://github.com/turkpin/admin-kit/discussions)
- **Email**: [admin-kit@turkpin.com](mailto:admin-kit@turkpin.com)

## 📄 License

AdminKit is open-sourced software licensed under the [MIT license](LICENSE).

## 🏆 v1.0.5 Features

- ✅ **Intelligent Installation** - Smart Docker setup with interactive prompts
- ✅ **Auto Environment Setup** - Zero manual configuration steps
- ✅ **One-Command Deployment** - Complete setup in seconds
- ✅ **Smart Docker Integration** - Auto-detects and configures for Docker
- ✅ **Migration-Ready State** - Instantly ready for database setup
- ✅ **User Experience Revolution** - Eliminated all manual steps
- ✅ **Production Ready** - Enterprise-grade stability
- ✅ **Global Solution** - Multi-language support with extensible i18n

---

**Set up your admin panels in seconds with AdminKit v1.0.5!** 🚀

*Modern, intelligent, zero-configuration admin panel solution.*

## 🔄 Version History

- **v1.0.5**: Intelligent installation with auto Docker setup
- **v1.0.4**: Complete environment variable system
- **v1.0.3**: Full CLI command suite
- **v1.0.2**: PHP 8+ compatibility fixes
- **v1.0.1**: Initial Packagist release
- **v1.0.0**: Full feature release
