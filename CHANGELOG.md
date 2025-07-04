# Changelog

All notable changes to AdminKit will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
