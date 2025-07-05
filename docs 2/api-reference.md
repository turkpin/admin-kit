# API Reference

This document provides a comprehensive reference for AdminKit's API, classes, and methods.

## Core Classes

### AdminKit

The main AdminKit class for configuring and managing your admin panel.

```php
namespace AdminKit;

class AdminKit
{
    public function __construct(App $app, array $config = [])
    public function addEntity(string $entityClass, array $config = []): self
    public function addDashboardWidget(string $name, array $config): self
    public function addCustomRoute(string $method, string $path, callable $handler): self
    public function getEntities(): array
    public function getWidgets(): array
    public function getConfig(): array
    public function getEntityManager(): EntityManagerInterface
    public function getSmarty(): Smarty
    public function getAuthService(): AuthService
    public function getContainer(): ContainerInterface
    public function getApp(): App
}
```

#### Constructor

```php
public function __construct(App $app, array $config = [])
```

**Parameters:**
- `$app` (App): Slim application instance
- `$config` (array): Configuration options

**Example:**
```php
$adminKit = new AdminKit($app, [
    'route_prefix' => '/admin',
    'brand_name' => 'My Admin Panel',
    'locale' => 'en',
]);
```

#### addEntity()

```php
public function addEntity(string $entityClass, array $config = []): self
```

Registers an entity with AdminKit for CRUD operations.

**Parameters:**
- `$entityClass` (string): Fully qualified entity class name
- `$config` (array): Entity configuration

**Configuration Options:**
- `title` (string): Display title for the entity
- `description` (string): Entity description
- `icon` (string): Icon name for navigation
- `fields` (array): Field configuration
- `filters` (array): Available filters
- `search` (array): Searchable fields
- `actions` (array): Enabled actions
- `permissions` (array): Permission requirements

**Example:**
```php
$adminKit->addEntity(User::class, [
    'title' => 'Users',
    'description' => 'Manage system users',
    'icon' => 'users',
    'fields' => [
        'name' => [
            'type' => 'text',
            'label' => 'Full Name',
            'required' => true,
        ],
        'email' => [
            'type' => 'email',
            'label' => 'Email Address',
            'required' => true,
        ],
    ],
    'filters' => ['role', 'isActive'],
    'search' => ['name', 'email'],
    'actions' => ['index', 'show', 'new', 'edit', 'delete'],
]);
```

#### addDashboardWidget()

```php
public function addDashboardWidget(string $name, array $config): self
```

Adds a widget to the dashboard.

**Parameters:**
- `$name` (string): Unique widget name
- `$config` (array): Widget configuration

**Configuration Options:**
- `title` (string): Widget title
- `type` (string): Widget type (counter, chart, list, custom)
- `value` (mixed|callable): Widget value or callback
- `icon` (string): Icon name
- `color` (string): Color theme
- `link` (string): Optional link URL

**Example:**
```php
$adminKit->addDashboardWidget('user_count', [
    'title' => 'Total Users',
    'type' => 'counter',
    'value' => function() use ($entityManager) {
        return $entityManager->getRepository(User::class)->count([]);
    },
    'icon' => 'users',
    'color' => 'blue',
]);
```

## Field Types

AdminKit supports various field types for entity forms:

### Text Field

```php
'fieldName' => [
    'type' => 'text',
    'label' => 'Field Label',
    'required' => true,
    'placeholder' => 'Enter value...',
    'help' => 'Help text',
    'maxlength' => 255,
]
```

### Email Field

```php
'email' => [
    'type' => 'email',
    'label' => 'Email Address',
    'required' => true,
    'placeholder' => 'user@example.com',
]
```

### Password Field

```php
'password' => [
    'type' => 'password',
    'label' => 'Password',
    'required' => true,
    'minlength' => 8,
    'help' => 'Minimum 8 characters',
]
```

### Number Field

```php
'price' => [
    'type' => 'number',
    'label' => 'Price',
    'min' => 0,
    'max' => 99999,
    'step' => 0.01,
    'currency' => 'USD',
]
```

### Boolean Field

```php
'isActive' => [
    'type' => 'boolean',
    'label' => 'Active',
    'default' => true,
]
```

### Choice Field

```php
'status' => [
    'type' => 'choice',
    'label' => 'Status',
    'choices' => [
        'draft' => 'Draft',
        'published' => 'Published',
        'archived' => 'Archived',
    ],
    'default' => 'draft',
]
```

### Date Field

```php
'birthDate' => [
    'type' => 'date',
    'label' => 'Birth Date',
    'format' => 'Y-m-d',
]
```

### DateTime Field

```php
'createdAt' => [
    'type' => 'datetime',
    'label' => 'Created At',
    'format' => 'Y-m-d H:i:s',
    'readonly' => true,
]
```

### Textarea Field

```php
'description' => [
    'type' => 'textarea',
    'label' => 'Description',
    'rows' => 5,
    'maxlength' => 1000,
]
```

### File Field

```php
'document' => [
    'type' => 'file',
    'label' => 'Document',
    'upload_dir' => 'documents',
    'max_size' => '10MB',
    'allowed_types' => ['pdf', 'doc', 'docx'],
]
```

### Image Field

```php
'avatar' => [
    'type' => 'image',
    'label' => 'Avatar',
    'upload_dir' => 'avatars',
    'max_size' => '2MB',
    'allowed_types' => ['jpg', 'jpeg', 'png'],
    'max_width' => 800,
    'max_height' => 600,
]
```

### Association Field

```php
'category' => [
    'type' => 'association',
    'label' => 'Category',
    'target_entity' => Category::class,
    'choice_label' => 'name',
    'required' => true,
]
```

### Collection Field

```php
'tags' => [
    'type' => 'collection',
    'label' => 'Tags',
    'entry_type' => 'text',
    'allow_add' => true,
    'allow_delete' => true,
]
```

## Services

### AuthService

Handles authentication and authorization.

```php
namespace AdminKit\Services;

class AuthService
{
    public function login(string $email, string $password): bool
    public function logout(): void
    public function isAuthenticated(): bool
    public function getCurrentUser(): ?User
    public function hasRole(string $role): bool
    public function hasPermission(string $permission): bool
    public function createUser(array $data): User
    public function updatePassword(User $user, string $password): void
}
```

### LocalizationService

Manages translations and localization.

```php
namespace AdminKit\Services;

class LocalizationService
{
    public function setLocale(string $locale): void
    public function getLocale(): string
    public function translate(string $key, array $params = []): string
    public function getSupportedLocales(): array
    public function loadTranslations(string $locale): void
}
```

### CacheService

Provides caching functionality.

```php
namespace AdminKit\Services;

class CacheService
{
    public function get(string $key, mixed $default = null): mixed
    public function set(string $key, mixed $value, int $ttl = 3600): bool
    public function delete(string $key): bool
    public function clear(): bool
    public function has(string $key): bool
}
```

## Events

AdminKit provides an event system for extending functionality:

### Entity Events

```php
// Before entity is created
AdminKit::on('entity.before_create', function($entity) {
    // Modify entity before creation
});

// After entity is created
AdminKit::on('entity.after_create', function($entity) {
    // Perform actions after creation
});

// Before entity is updated
AdminKit::on('entity.before_update', function($entity) {
    // Modify entity before update
});

// After entity is updated
AdminKit::on('entity.after_update', function($entity) {
    // Perform actions after update
});

// Before entity is deleted
AdminKit::on('entity.before_delete', function($entity) {
    // Check if deletion is allowed
});

// After entity is deleted
AdminKit::on('entity.after_delete', function($entity) {
    // Cleanup after deletion
});
```

### Authentication Events

```php
// User login
AdminKit::on('auth.login', function($user) {
    // Log login event
});

// User logout
AdminKit::on('auth.logout', function($user) {
    // Log logout event
});

// Failed login attempt
AdminKit::on('auth.login_failed', function($email) {
    // Handle failed login
});
```

## Configuration Options

### Application Configuration

```php
$config = [
    // Basic settings
    'route_prefix' => '/admin',
    'brand_name' => 'AdminKit',
    'theme' => 'default',
    'locale' => 'en',
    
    // Pagination
    'pagination_limit' => 20,
    
    // Security
    'auth_required' => true,
    'rbac_enabled' => true,
    'csrf_protection' => true,
    
    // Performance
    'cache_enabled' => true,
    'template_cache' => true,
    
    // File uploads
    'upload_path' => 'uploads/',
    'max_upload_size' => '10M',
    
    // Date/time
    'date_format' => 'Y-m-d H:i:s',
    'timezone' => 'UTC',
];
```

### Entity Configuration

```php
$entityConfig = [
    'title' => 'Entity Title',
    'description' => 'Entity description',
    'icon' => 'icon-name',
    
    // Fields configuration
    'fields' => [
        // Field definitions
    ],
    
    // List view configuration
    'list' => [
        'fields' => ['field1', 'field2'],
        'sort' => ['field1' => 'ASC'],
        'filters' => ['field1', 'field2'],
        'search' => ['field1', 'field2'],
        'actions' => ['show', 'edit', 'delete'],
        'batch_actions' => ['delete', 'activate'],
    ],
    
    // Form configuration
    'form' => [
        'fields' => ['field1', 'field2'],
        'groups' => [
            'basic' => ['field1'],
            'advanced' => ['field2'],
        ],
    ],
    
    // Permissions
    'permissions' => [
        'list' => ['ROLE_USER'],
        'show' => ['ROLE_USER'],
        'new' => ['ROLE_ADMIN'],
        'edit' => ['ROLE_ADMIN'],
        'delete' => ['ROLE_SUPER_ADMIN'],
    ],
    
    // Actions
    'actions' => ['index', 'show', 'new', 'edit', 'delete'],
    'custom_actions' => [
        'action_name' => [
            'label' => 'Action Label',
            'icon' => 'icon-name',
            'handler' => 'ControllerClass::method',
        ],
    ],
];
```

## CLI Commands

AdminKit provides several CLI commands for development and maintenance:

### User Management

```bash
# Create a new user
php bin/adminkit user:create email password "Full Name"

# Create an admin user
php bin/adminkit user:create --admin admin@example.com password "Admin User"

# List all users
php bin/adminkit user:list

# Delete a user
php bin/adminkit user:delete email@example.com
```

### Database Management

```bash
# Run migrations
php bin/adminkit migrate

# Run migrations with fresh database
php bin/adminkit migrate --fresh

# Seed database with sample data
php bin/adminkit migrate --seed

# Test database connection
php bin/adminkit db:test
```

### Cache Management

```bash
# Clear all caches
php bin/adminkit cache:clear

# Clear template cache
php bin/adminkit cache:clear --templates

# Clear application cache
php bin/adminkit cache:clear --app
```

### Development

```bash
# Start development server
php bin/adminkit serve

# Start on specific port
php bin/adminkit serve --port=8080

# Show application version
php bin/adminkit version

# Show configuration
php bin/adminkit config:show
```

## Error Handling

AdminKit provides comprehensive error handling:

### Custom Exceptions

```php
namespace AdminKit\Exceptions;

class AdminKitException extends \Exception {}
class EntityNotFoundException extends AdminKitException {}
class ValidationException extends AdminKitException {}
class AuthenticationException extends AdminKitException {}
class AuthorizationException extends AdminKitException {}
```

### Error Handlers

```php
// Register custom error handler
AdminKit::errorHandler(function($exception) {
    // Handle exception
    error_log($exception->getMessage());
    
    // Return custom response
    return [
        'error' => true,
        'message' => 'An error occurred',
    ];
});
```

## Security

### CSRF Protection

AdminKit automatically includes CSRF protection for forms:

```php
// CSRF tokens are automatically added to forms
// No additional configuration required
```

### Role-Based Access Control

```php
// Define roles and permissions
$adminKit->configureRoles([
    'ROLE_USER' => [
        'permissions' => ['read'],
    ],
    'ROLE_ADMIN' => [
        'permissions' => ['read', 'write'],
        'inherits' => ['ROLE_USER'],
    ],
    'ROLE_SUPER_ADMIN' => [
        'permissions' => ['read', 'write', 'delete'],
        'inherits' => ['ROLE_ADMIN'],
    ],
]);
```

### Input Validation

```php
// Field validation rules
'email' => [
    'type' => 'email',
    'required' => true,
    'rules' => [
        'email' => true,
        'unique' => User::class,
    ],
],
```

## Performance Optimization

### Caching

```php
// Enable caching
$config['cache_enabled'] = true;
$config['template_cache'] = true;

// Configure cache drivers
$config['cache_driver'] = 'redis';
$config['redis_host'] = '127.0.0.1';
```

### Database Optimization

```php
// Configure Doctrine for performance
$config['doctrine_cache'] = true;
$config['doctrine_proxy_cache'] = true;
$config['doctrine_query_cache'] = true;
```

### Asset Optimization

```php
// Minify and compress assets
php bin/adminkit assets:compile --minify

// Enable asset versioning
$config['asset_versioning'] = true;
```

For more detailed examples and advanced usage, see the [Examples](examples/) directory.
