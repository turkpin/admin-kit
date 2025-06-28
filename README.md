# AdminKit

Modern admin panel generator for Slim Framework with Smarty templates and Doctrine ORM.

## Features

- 🚀 **Easy Setup**: Quick installation and configuration
- 🎨 **Modern UI**: Built with Tailwind CSS
- 🔐 **RBAC**: Role-Based Access Control system
- 📊 **CRUD Generator**: Automatic CRUD operations for entities
- 🎯 **Field Types**: Rich field types (text, email, image, boolean, etc.)
- 🔍 **Filters & Search**: Built-in filtering and search capabilities
- 📱 **Responsive**: Mobile-friendly design
- 🌐 **Turkish Support**: Turkish language support

## Requirements

- PHP 8.1 or higher
- Slim Framework 4
- Smarty 4
- Doctrine ORM 2.15+
- MySQL/PostgreSQL

## Installation

```bash
composer require turkpin/admin-kit
```

## Quick Start

### 1. Basic Setup

```php
<?php
use Slim\Factory\AppFactory;
use Doctrine\ORM\EntityManager;
use Smarty;
use Turkpin\AdminKit\AdminKit;

// Create Slim app
$app = AppFactory::create();

// Setup Doctrine EntityManager
$entityManager = /* your EntityManager setup */;

// Setup Smarty
$smarty = new Smarty();

// Create AdminKit instance
$adminKit = new AdminKit($app, [
    'doctrine' => $entityManager,
    'smarty' => $smarty,
    'route_prefix' => '/admin',
    'auth_required' => true,
    'rbac_enabled' => true,
]);

// Add entities
$adminKit->addEntity(User::class, [
    'title' => 'Kullanıcılar',
    'fields' => [
        'name' => [
            'type' => 'text',
            'label' => 'Ad Soyad',
            'required' => true
        ],
        'email' => [
            'type' => 'email',
            'label' => 'E-posta',
            'required' => true
        ],
        'avatar' => [
            'type' => 'image',
            'label' => 'Avatar',
            'upload_dir' => 'uploads/avatars/'
        ],
        'isActive' => [
            'type' => 'boolean',
            'label' => 'Aktif'
        ],
        'roles' => [
            'type' => 'association',
            'label' => 'Roller',
            'target_entity' => Role::class,
            'multiple' => true
        ]
    ],
    'filters' => ['name', 'email', 'isActive'],
    'actions' => ['index', 'show', 'new', 'edit', 'delete'],
    'permissions' => ['admin', 'user_manager']
]);

// Add dashboard widgets
$adminKit->addDashboardWidget('users_count', [
    'title' => 'Toplam Kullanıcılar',
    'value' => fn() => $entityManager->getRepository(User::class)->count([]),
    'icon' => 'users',
    'color' => 'blue'
]);

$adminKit->addDashboardWidget('active_users', [
    'title' => 'Aktif Kullanıcılar',
    'value' => fn() => $entityManager->getRepository(User::class)->count(['isActive' => true]),
    'icon' => 'user',
    'color' => 'green'
]);

$app->run();
```

### 2. Entity Configuration

```php
// Basic entity setup
$adminKit->addEntity(Product::class, [
    'title' => 'Ürünler',
    'fields' => [
        'name' => ['type' => 'text', 'required' => true],
        'price' => ['type' => 'money', 'currency' => 'TL'],
        'description' => ['type' => 'textarea'],
        'image' => ['type' => 'image', 'upload_dir' => 'products/'],
        'isActive' => ['type' => 'boolean'],
        'category' => ['type' => 'association', 'target_entity' => Category::class]
    ]
]);
```

### 3. Field Types

AdminKit supports various field types:

| Type | Description | Options |
|------|-------------|---------|
| `text` | Text input | `required`, `max_length` |
| `email` | Email input | `required` |
| `password` | Password input | `required`, `min_length` |
| `textarea` | Textarea input | `rows`, `cols` |
| `number` | Number input | `min`, `max`, `step` |
| `money` | Money input | `currency`, `decimals` |
| `boolean` | Checkbox/Switch | - |
| `choice` | Select dropdown | `choices`, `multiple` |
| `date` | Date picker | `format` |
| `datetime` | DateTime picker | `format` |
| `image` | Image upload | `upload_dir`, `allowed_extensions` |
| `file` | File upload | `upload_dir`, `allowed_extensions` |
| `association` | Entity relation | `target_entity`, `multiple` |

### 4. Dashboard Widgets

```php
// Stat widget
$adminKit->addDashboardWidget('revenue', [
    'title' => 'Bu Ay Gelir',
    'value' => fn() => calculateMonthlyRevenue(),
    'icon' => 'chart-bar',
    'color' => 'green',
    'type' => 'stat'
]);

// Chart widget
$adminKit->addDashboardWidget('sales_chart', [
    'title' => 'Satış Grafiği',
    'type' => 'chart',
    'chart_type' => 'line',
    'data' => fn() => getSalesData()
]);
```

### 5. Custom Routes

```php
// Add custom route
$adminKit->addCustomRoute('GET', '/reports', function($request, $response) {
    // Custom logic here
    return $response;
});
```

### 6. Authentication & RBAC

```php
// Create roles and permissions
$authService = $adminKit->getAuthService();

// Create permissions
$userIndexPermission = $authService->createPermission('user.index', 'View users', 'user', 'index');
$userCreatePermission = $authService->createPermission('user.create', 'Create users', 'user', 'create');

// Create role
$adminRole = $authService->createRole('admin', 'Administrator');

// Assign permissions to role
$authService->assignPermissionToRole($adminRole, $userIndexPermission);
$authService->assignPermissionToRole($adminRole, $userCreatePermission);

// Create user
$user = $authService->createUser([
    'name' => 'Admin User',
    'email' => 'admin@example.com',
    'password' => 'password123'
]);

// Assign role to user
$authService->assignRole($user, $adminRole);
```

## Configuration Options

```php
$config = [
    'route_prefix' => '/admin',           // Admin panel URL prefix
    'template_path' => 'templates/',      // Smarty templates path
    'assets_path' => '/assets/admin',     // Assets URL path
    'auth_required' => true,              // Enable authentication
    'rbac_enabled' => true,               // Enable RBAC
    'pagination_limit' => 20,             // Items per page
    'upload_path' => 'uploads/',          // File upload directory
    'theme' => 'default',                 // UI theme
    'brand_name' => 'AdminKit',           // Brand name
    'dashboard_title' => 'Dashboard',     // Dashboard page title
    'date_format' => 'Y-m-d H:i:s',      // Date display format
    'locale' => 'tr',                     // Language locale
    'csrf_protection' => true,            // Enable CSRF protection
];
```

## Templates

AdminKit uses Smarty templates with custom functions and modifiers:

### Custom Functions
- `{url route="users"}` - Generate URLs
- `{asset path="css/admin.css"}` - Generate asset URLs
- `{icon name="user" class="w-5 h-5"}` - Render icons
- `{flash type="success" message="Saved!"}` - Show flash messages

### Custom Modifiers
- `{$date|date_format:"d.m.Y"}` - Format dates
- `{$text|truncate:100}` - Truncate text
- `{$amount|money:"TL"}` - Format money
- `{$boolean|bool_text:"Evet":"Hayır"}` - Boolean to text

## License

MIT License. See [LICENSE](LICENSE) file for details.

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Support

For support, email info@turkpin.com or create an issue on GitHub.
