<?php

declare(strict_types=1);

/**
 * AdminKit Demo Application
 * 
 * This file demonstrates how to use AdminKit with sample entities.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use AdminKit\AdminKit;

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

// Create Slim app
$app = AppFactory::create();

// Error handling
$app->addErrorMiddleware(true, true, true);
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

// Initialize AdminKit with demo configuration
$adminKit = new AdminKit($app, [
    'route_prefix' => '/demo',
    'brand_name' => 'AdminKit Demo',
    'theme' => 'default',
    'locale' => 'en',
    'pagination_limit' => 10,
]);

// Add User entity
$adminKit->addEntity(\Demo\Entity\User::class, [
    'title' => 'Users',
    'description' => 'Manage system users',
    'icon' => 'users',
    'fields' => [
        'name' => [
            'type' => 'text',
            'label' => 'Full Name',
            'required' => true,
            'placeholder' => 'Enter full name',
        ],
        'email' => [
            'type' => 'email',
            'label' => 'Email Address',
            'required' => true,
            'placeholder' => 'user@example.com',
        ],
        'password' => [
            'type' => 'password',
            'label' => 'Password',
            'required' => true,
            'help' => 'Minimum 8 characters',
        ],
        'role' => [
            'type' => 'choice',
            'label' => 'Role',
            'choices' => [
                'admin' => 'Administrator',
                'user' => 'Regular User',
                'moderator' => 'Moderator',
            ],
            'default' => 'user',
        ],
        'isActive' => [
            'type' => 'boolean',
            'label' => 'Active',
            'default' => true,
        ],
        'avatar' => [
            'type' => 'image',
            'label' => 'Avatar',
            'upload_dir' => 'avatars',
            'max_size' => '2MB',
        ],
        'bio' => [
            'type' => 'textarea',
            'label' => 'Biography',
            'rows' => 4,
        ],
        'birthDate' => [
            'type' => 'date',
            'label' => 'Birth Date',
        ],
        'createdAt' => [
            'type' => 'datetime',
            'label' => 'Created At',
            'readonly' => true,
        ],
    ],
    'filters' => ['role', 'isActive'],
    'search' => ['name', 'email'],
    'actions' => ['index', 'show', 'new', 'edit', 'delete'],
]);

// Add Product entity
$adminKit->addEntity(\Demo\Entity\Product::class, [
    'title' => 'Products',
    'description' => 'Manage product catalog',
    'icon' => 'package',
    'fields' => [
        'name' => [
            'type' => 'text',
            'label' => 'Product Name',
            'required' => true,
        ],
        'slug' => [
            'type' => 'text',
            'label' => 'URL Slug',
            'help' => 'Auto-generated from name if empty',
        ],
        'description' => [
            'type' => 'textarea',
            'label' => 'Description',
            'rows' => 5,
        ],
        'price' => [
            'type' => 'number',
            'label' => 'Price',
            'required' => true,
            'min' => 0,
            'step' => 0.01,
            'currency' => 'USD',
        ],
        'comparePrice' => [
            'type' => 'number',
            'label' => 'Compare at Price',
            'min' => 0,
            'step' => 0.01,
            'currency' => 'USD',
        ],
        'category' => [
            'type' => 'association',
            'label' => 'Category',
            'target_entity' => \Demo\Entity\Category::class,
            'choice_label' => 'name',
            'required' => true,
        ],
        'image' => [
            'type' => 'image',
            'label' => 'Product Image',
            'upload_dir' => 'products',
            'max_size' => '5MB',
        ],
        'gallery' => [
            'type' => 'collection',
            'label' => 'Image Gallery',
            'entry_type' => 'image',
            'allow_add' => true,
            'allow_delete' => true,
        ],
        'sku' => [
            'type' => 'text',
            'label' => 'SKU',
            'help' => 'Stock Keeping Unit',
        ],
        'stock' => [
            'type' => 'number',
            'label' => 'Stock Quantity',
            'min' => 0,
        ],
        'weight' => [
            'type' => 'number',
            'label' => 'Weight (kg)',
            'min' => 0,
            'step' => 0.1,
        ],
        'isActive' => [
            'type' => 'boolean',
            'label' => 'Active',
            'default' => true,
        ],
        'isFeatured' => [
            'type' => 'boolean',
            'label' => 'Featured',
            'default' => false,
        ],
        'tags' => [
            'type' => 'text',
            'label' => 'Tags',
            'help' => 'Comma-separated tags',
        ],
    ],
    'filters' => ['category', 'isActive', 'isFeatured'],
    'search' => ['name', 'sku', 'description'],
    'actions' => ['index', 'show', 'new', 'edit', 'delete'],
]);

// Add Category entity
$adminKit->addEntity(\Demo\Entity\Category::class, [
    'title' => 'Categories',
    'description' => 'Organize products into categories',
    'icon' => 'folder',
    'fields' => [
        'name' => [
            'type' => 'text',
            'label' => 'Category Name',
            'required' => true,
        ],
        'slug' => [
            'type' => 'text',
            'label' => 'URL Slug',
        ],
        'description' => [
            'type' => 'textarea',
            'label' => 'Description',
            'rows' => 3,
        ],
        'parent' => [
            'type' => 'association',
            'label' => 'Parent Category',
            'target_entity' => \Demo\Entity\Category::class,
            'choice_label' => 'name',
            'required' => false,
        ],
        'image' => [
            'type' => 'image',
            'label' => 'Category Image',
            'upload_dir' => 'categories',
        ],
        'isActive' => [
            'type' => 'boolean',
            'label' => 'Active',
            'default' => true,
        ],
        'sortOrder' => [
            'type' => 'number',
            'label' => 'Sort Order',
            'default' => 0,
        ],
    ],
    'filters' => ['parent', 'isActive'],
    'search' => ['name', 'description'],
    'actions' => ['index', 'show', 'new', 'edit', 'delete'],
]);

// Add dashboard widgets
$adminKit->addDashboardWidget('users_count', [
    'title' => 'Total Users',
    'type' => 'counter',
    'value' => function() {
        // In real implementation, this would query the database
        return rand(150, 300);
    },
    'icon' => 'users',
    'color' => 'blue',
    'link' => '/demo/user',
]);

$adminKit->addDashboardWidget('products_count', [
    'title' => 'Products',
    'type' => 'counter',
    'value' => function() {
        return rand(500, 1200);
    },
    'icon' => 'package',
    'color' => 'green',
    'link' => '/demo/product',
]);

$adminKit->addDashboardWidget('categories_count', [
    'title' => 'Categories',
    'type' => 'counter',
    'value' => function() {
        return rand(20, 50);
    },
    'icon' => 'folder',
    'color' => 'purple',
    'link' => '/demo/category',
]);

$adminKit->addDashboardWidget('revenue', [
    'title' => 'Monthly Revenue',
    'type' => 'currency',
    'value' => function() {
        return '$' . number_format(rand(15000, 45000), 2);
    },
    'icon' => 'dollar-sign',
    'color' => 'yellow',
    'trend' => '+12.5%',
]);

// Add welcome route
$app->get('/', function ($request, $response) {
    $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AdminKit Demo</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card { background: white; border-radius: 12px; padding: 3rem; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.1); max-width: 600px; }
        h1 { color: #374151; margin-bottom: 1rem; font-size: 2.5rem; }
        p { color: #6b7280; margin-bottom: 2rem; line-height: 1.6; font-size: 1.1rem; }
        .features { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin: 2rem 0; }
        .feature { padding: 1rem; background: #f8fafc; border-radius: 8px; }
        .feature h3 { color: #374151; margin-bottom: 0.5rem; }
        .feature p { color: #6b7280; margin: 0; font-size: 0.9rem; }
        .btn { display: inline-block; padding: 0.75rem 1.5rem; background: #3b82f6; color: white; text-decoration: none; border-radius: 6px; font-weight: 600; margin: 0.5rem; }
        .btn:hover { background: #2563eb; }
        .btn-secondary { background: #10b981; }
        .btn-secondary:hover { background: #059669; }
    </style>
</head>
<body>
    <div class="card">
        <h1>🚀 AdminKit Demo</h1>
        <p>Explore AdminKit\'s powerful features with this interactive demo application. See how easy it is to create a complete admin panel!</p>
        
        <div class="features">
            <div class="feature">
                <h3>👥 Users</h3>
                <p>User management with roles and permissions</p>
            </div>
            <div class="feature">
                <h3>📦 Products</h3>
                <p>Complete product catalog management</p>
            </div>
            <div class="feature">
                <h3>📁 Categories</h3>
                <p>Hierarchical category organization</p>
            </div>
            <div class="feature">
                <h3>📊 Dashboard</h3>
                <p>Real-time analytics and widgets</p>
            </div>
        </div>
        
        <a href="/demo" class="btn">Enter Demo Admin Panel</a>
        <a href="/welcome" class="btn btn-secondary">Back to Welcome</a>
        
        <p style="margin-top: 2rem; font-size: 0.9rem; color: #9ca3af;">
            <strong>Note:</strong> This is a demonstration with sample data. No real database operations are performed.
        </p>
    </div>
</body>
</html>';
    
    $response->getBody()->write($html);
    return $response;
});

// Run the application
$app->run();
