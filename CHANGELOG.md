# Changelog

All notable changes to AdminKit will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.5] - 2025-01-07

### Added
- **Intelligent Installation Workflow**: Smart Docker setup with interactive prompts
- **Automatic Environment Configuration**: .env file auto-creation with Docker awareness
- **One-Command Package Setup**: Complete installation with zero manual steps
- **Smart Docker File Publishing**: Automatic Docker files deployment during install
- **Environment-Aware Configuration**: Auto-detects Docker setup and configures accordingly

### Enhanced
- **InstallCommand Complete Overhaul**: 
  - Interactive Docker setup prompt during installation
  - Automatic .env file creation from .env.example or package defaults
  - Smart environment variable updating for Docker configurations
  - Docker files auto-publishing with `--with-docker` option
  - Intelligent next steps guidance based on installation choices
- **User Experience Revolution**: 
  - Single command setup: `composer require turkpin/admin-kit && php vendor/bin/adminkit install`
  - No more manual `env:copy` steps required
  - Docker-ready configuration in seconds
  - Migration-ready state immediately after install

### Fixed
- **Package Installation Flow**: Eliminated manual environment file copying
- **Docker Integration**: Seamless package-to-Docker workflow
- **Environment Variable Priority**: Proper Docker service hostnames auto-configuration
- **Installation Guidance**: Context-aware next steps based on setup choices

### Technical
- **Smart Environment Detection**: Automatic Docker vs local configuration
- **File Publishing Logic**: Intelligent Docker files deployment
- **Configuration Management**: Environment-aware variable updates
- **User Experience**: Interactive prompts with sensible defaults

### Workflow
**Before v1.0.5:**
```bash
composer require turkpin/admin-kit
php vendor/bin/adminkit install
php vendor/bin/adminkit env:copy  # Manual step
# Docker files manual copy
# Manual .env editing for Docker
```

**After v1.0.5:**
```bash
composer require turkpin/admin-kit
php vendor/bin/adminkit install  # Interactive Docker prompt
# ✅ .env auto-created and configured
# ✅ Docker files auto-published 
# ✅ Ready for docker-compose up
```

## [1.0.4] - 2025-01-07

### Added
- **Complete Environment Variable System**: Comprehensive .env support with 50+ configuration options
- **Enhanced ConfigService**: Full environment variable integration with type conversion and validation
- **Docker Infrastructure**: Complete containerization with production-ready setup
  - Multi-stage Dockerfile (development/production/nginx)
  - Docker Compose with 7 services (PHP, Nginx, MySQL, Redis, MailHog, Adminer, Queue Worker)
  - Production-optimized configurations with security headers and OPcache
- **Enhanced CLI Commands**:
  - `env:copy` - Copy .env.example to .env with interactive setup
  - `env:check` - Validate environment configuration and database connectivity
  - `docker:up` - Start Docker containers with build and detach options
  - `docker:down` - Stop containers with volume cleanup options
- **Professional Development Environment**:
  - Complete Laravel-style .env.example with all AdminKit features
  - Automatic environment detection and type conversion
  - Database connection validation and error reporting
  - One-command Docker setup for instant development

### Enhanced
- **ConfigService Overhaul**: Complete rewrite with environment variable priority
- **Configuration Management**: Dot notation support, validation, and auto-generation
- **Database Integration**: Enhanced connection handling with timeout and SSL support
- **CLI Installation Flow**: Improved guidance with environment setup steps
- **Development Workflow**: Docker-first approach with hot reloading

### Fixed
- **Environment Variable Loading**: Proper parsing of .env files with quote handling
- **Configuration Priority**: Environment variables now properly override defaults
- **Type Conversion**: Automatic conversion of string env values to proper types (bool, int, float)
- **Project Root Detection**: Improved logic for finding project root in various scenarios

### Technical
- **ConfigService**: Complete environment variable integration with fallbacks
- **Docker Configuration**: Production-ready multi-stage builds with optimization
- **CLI Commands**: Enhanced error handling and user feedback
- **Environment Management**: Professional .env handling with validation

## [1.0.3] - 2025-01-07

### Added
- **Complete CLI Command Suite**: Added all missing CLI commands promised in documentation
- **User Management Commands**:
  - `user:create` - Create admin users with interactive prompts
  - Support for name, email, password arguments or interactive input
  - Automatic password generation if not provided
- **Database Commands**:
  - `migrate` - Run database migrations with progress tracking
  - Migration table management and execution tracking
  - SQL file processing from migrations directory
- **Development Commands**:
  - `serve` - Start PHP development server with configurable host/port
  - Built-in public directory detection and validation
- **Queue Management**:
  - `queue:work` - Queue worker with timeout and queue selection
  - Basic job processing simulation and logging
- **Cache Management**:
  - `cache:clear` - Clear cache files with recursive directory cleaning
- **Enhanced Installation**:
  - Improved installation flow with uploads directory creation
  - Better next steps guidance after installation

### Enhanced
- **Database Integration**: Added PDO-based database connection helper
- **Interactive CLI**: Added interactive prompts for user-friendly experience
- **Error Handling**: Comprehensive error messages and validation
- **Progress Feedback**: Real-time feedback for all operations
- **Configuration Validation**: Better config file and directory validation

### Fixed
- **CLI Command Coverage**: Fixed missing commands that were documented but not implemented
- **User Experience**: Resolved "command not found" errors for advertised features
- **Database Connectivity**: Proper database connection with error handling
- **Migration System**: Working migration execution and tracking

### Technical
- Updated CLI application version to 1.0.3
- Added database helper function with PDO integration
- Improved command class structure and error handling
- Enhanced user input validation and sanitization

## [1.0.2] - 2025-01-07

### Fixed
- **PHP 8+ Compatibility**: Fixed nullable parameter deprecation warning in `adminkit_translate()` function
- **Symfony Console Commands**: Fixed command name configuration for Symfony Console 7.x compatibility
- **CLI Tools**: Resolved "empty command name" error in bin/adminkit executable
- **Type Declarations**: Updated helper functions with proper PHP 8+ nullable and mixed type declarations

### Changed
- Updated `adminkit_translate()` function signature to use `?string $locale = null`
- Updated `adminkit_env()` function to use `mixed` type for default parameter
- Modernized Symfony Console command configuration using `setName()` in `configure()` method
- Version bumped to 1.0.2 in CLI application

### Technical
- Improved PHP 8.1+ compatibility across all helper functions
- Fixed Symfony Console 7.x deprecation warnings
- Enhanced CLI command structure for better maintainability

## [1.0.1] - 2025-01-07

### Added
- Initial Packagist publication
- Automatic package discovery and installation

### Fixed
- Minor package metadata improvements

## [1.0.0] - 2025-01-07

### Added
- **Complete EasyAdmin Feature Parity**: 100% feature compatibility with Symfony EasyAdmin 4.x
- **Enterprise Security Features**:
  - Two-Factor Authentication (TOTP) with backup codes
  - Advanced audit logging and change tracking
  - Role-based access control (RBAC) system
  - Session management with timeout and security controls

- **Performance & Scalability**:
  - Background job processing with 4-priority queue system
  - Real-time performance monitoring and profiling
  - Multi-layer caching system (File, Redis, Memory)
  - Slow query detection and optimization suggestions

- **Real-time Features**:
  - WebSocket integration for live updates
  - Server-Sent Events (SSE) fallback
  - User presence tracking
  - Real-time notifications across 5 channels

- **Advanced UI/UX**:
  - Asset management with Webpack/Vite integration
  - Dynamic forms with conditional logic
  - Multi-step wizard forms with auto-save
  - Breadcrumb navigation system
  - 4 built-in themes (Light, Dark, Blue, Green)

- **Data Management**:
  - 14 comprehensive field types
  - Advanced filtering with 16 operators
  - Batch operations with queue integration
  - Export/Import in 5 formats (CSV, Excel, JSON, XML, PDF)
  - Global search across entities

- **Internationalization**:
  - Native Turkish language support
  - 600+ translation keys in Turkish and English
  - Complete localization system
  - Turkish-first developer experience

- **Developer Experience**:
  - Plugin architecture with hook/event system
  - CLI tools for installation and management
  - Service provider pattern for dependency injection
  - Comprehensive documentation system
  - Modern PHP 8.1+ codebase

- **Package Features**:
  - Composer package for easy installation
  - CLI installer with asset publishing
  - Service provider for automatic configuration
  - Helper functions for common operations
  - Production-ready deployment tools

### Technical Features
- **14 Field Types**: Text, Textarea, Email, Password, Number, Money, Date, DateTime, Boolean, Choice, File, Image, Association, Collection
- **24 Enterprise Services**: Complete service ecosystem for modern admin panels
- **4 Priority Queues**: Critical, High, Default, Low with cron scheduling
- **5 Notification Channels**: Toast, Flash, Alert, Email, Database
- **16 Filter Operators**: Comprehensive filtering system
- **REST API**: Automatic endpoint generation
- **Modern Stack**: PHP 8.1+, Tailwind CSS, Doctrine ORM, Smarty Templates

### Documentation
- Complete installation and setup guides
- 5-minute quick start tutorial
- Comprehensive service documentation
- Real-world examples and use cases
- Turkish-focused developer resources
- Production deployment instructions

### Security
- CSRF protection
- Rate limiting
- SQL injection prevention
- XSS protection
- Secure file uploads
- Password strength requirements

### Performance
- Database query optimization
- Asset minification and compression
- Memory usage monitoring
- Cache hit rate tracking
- Response time analysis
- Resource usage optimization

## [0.9.0] - 2024-12-15

### Added
- Initial development release
- Basic CRUD operations
- User authentication
- Simple admin interface

### Changed
- Improved database integration
- Enhanced security measures

### Fixed
- Various bug fixes and improvements

---

**AdminKit** - Modern Turkish-first enterprise admin panel solution
