# Getting Started with AdminKit

AdminKit is a powerful, modern admin panel toolkit for PHP applications. This guide will help you get up and running quickly.

## Requirements

- PHP 8.0 or higher
- MySQL 5.7+ or MariaDB 10.2+
- Composer
- Web server (Apache/Nginx)

### Required PHP Extensions

- PDO
- PDO MySQL
- JSON
- Mbstring
- Zip

## Installation

### 1. Install via Composer

```bash
composer create-project turkpin/admin-kit my-admin-panel
cd my-admin-panel
```

Or add to existing project:

```bash
composer require turkpin/admin-kit
```

### 2. Web-based Installation

Navigate to your application in a browser. AdminKit will automatically redirect you to the installation wizard at `/install`.

Follow these steps:

1. **Welcome** - Introduction to AdminKit features
2. **System Requirements** - Automatic check of your system
3. **Database Configuration** - Set up your database connection
4. **Admin User Setup** - Create your administrator account
5. **Installation Complete** - You're ready to go!

### 3. Manual Installation

If you prefer manual setup:

#### Create Environment File

Copy `.env.example` to `.env` and configure:

```env
# Application Settings
APP_NAME=AdminKit
APP_URL=http://localhost
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_LOCALE=en

# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=adminkit
DB_USERNAME=root
DB_PASSWORD=

# AdminKit Configuration
ADMINKIT_ROUTE_PREFIX=/admin
ADMINKIT_BRAND_NAME=AdminKit
```

#### Run Migrations

```bash
php bin/adminkit migrate
```

#### Create Admin User

```bash
php bin/adminkit user:create --admin admin@example.com password123 "Admin User"
```

## Basic Usage

### 1. Define Your Entities

AdminKit works with Doctrine entities. Create your entity classes:

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'products')]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $price;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    // Getters and setters...
}
```

### 2. Configure AdminKit

Create your admin configuration:

```php
<?php
// public/index.php or your bootstrap file

use AdminKit\AdminKit;
use Slim\Factory\AppFactory;

$app = AppFactory::create();

$adminKit = new AdminKit($app, [
    'route_prefix' => '/admin',
    'brand_name' => 'My Admin Panel',
]);

// Add your entities
$adminKit->addEntity(\App\Entity\Product::class, [
    'title' => 'Products',
    'fields' => [
        'name' => [
            'type' => 'text',
            'label' => 'Product Name',
            'required' => true
        ],
        'price' => [
            'type' => 'number',
            'label' => 'Price',
            'required' => true
        ],
        'description' => [
            'type' => 'textarea',
            'label' => 'Description'
        ]
    ],
    'filters' => ['name', 'price'],
    'actions' => ['index', 'show', 'new', 'edit', 'delete']
]);

// Add dashboard widgets
$adminKit->addDashboardWidget('products_count', [
    'title' => 'Total Products',
    'value' => function() use ($entityManager) {
        return $entityManager->getRepository(\App\Entity\Product::class)->count([]);
    },
    'icon' => 'box',
    'color' => 'blue'
]);

$app->run();
```

### 3. Field Types

AdminKit supports various field types:

- `text` - Single line text input
- `textarea` - Multi-line text input
- `email` - Email input with validation
- `password` - Password input
- `number` - Numeric input
- `boolean` - Checkbox
- `choice` - Select dropdown
- `date` - Date picker
- `datetime` - Date and time picker
- `file` - File upload
- `image` - Image upload with preview
- `association` - Entity relationships

### 4. Access Your Admin Panel

Once configured, access your admin panel at:

```
http://your-domain/admin
```

Default login credentials (if created via installation):
- Email: As specified during installation
- Password: As specified during installation

## Development Server

For development, you can use the built-in server:

```bash
php bin/adminkit serve
```

This will start a development server at `http://localhost:8000`.

## Next Steps

- [Configuration Reference](configuration.md)
- [Field Types Guide](field-types.md)
- [API Reference](api-reference.md)
- [Examples](examples/)

## Troubleshooting

### Common Issues

**Installation page keeps showing**
- Check that `.env` file exists and is properly configured
- Verify database connection settings
- Ensure `var/` directory is writable

**Blank page or errors**
- Check web server error logs
- Verify all required PHP extensions are installed
- Enable debug mode: `APP_DEBUG=true` in `.env`

**Database connection errors**
- Verify database credentials in `.env`
- Ensure database exists
- Check that MySQL/MariaDB is running

### Getting Help

- [GitHub Issues](https://github.com/oktayaydogan/admin-kit/issues)
- [Documentation](https://github.com/oktayaydogan/admin-kit/wiki)
- [Discussions](https://github.com/oktayaydogan/admin-kit/discussions)

## Contributing

AdminKit is open source! Contributions are welcome:

1. Fork the repository
2. Create your feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

See [CONTRIBUTING.md](../CONTRIBUTING.md) for detailed guidelines.
