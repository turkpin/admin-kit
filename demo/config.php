<?php

declare(strict_types=1);

/**
 * AdminKit Demo Configuration
 * 
 * This file provides configuration for the demo application
 */

return [
    // Demo Application Settings
    'demo' => [
        'name' => 'AdminKit Demo',
        'description' => 'Interactive demonstration of AdminKit features',
        'version' => '1.0.7',
        'author' => 'AdminKit Team',
        'url' => '/demo',
    ],

    // Database Configuration for Demo
    'database' => [
        'driver' => 'pdo_sqlite',
        'path' => __DIR__ . '/data/demo.sqlite',
        'memory' => false,
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ],
    ],

    // Entity Configuration
    'entities' => [
        'User' => [
            'class' => 'Demo\\Entity\\User',
            'title' => 'Users',
            'description' => 'Manage system users with roles and permissions',
            'icon' => 'users',
            'menu_order' => 10,
            'per_page' => 15,
            'default_sort' => ['name' => 'ASC'],
            'features' => [
                'search' => true,
                'filters' => true,
                'export' => true,
                'bulk_actions' => true,
            ],
        ],
        'Product' => [
            'class' => 'Demo\\Entity\\Product',
            'title' => 'Products',
            'description' => 'Manage product catalog with pricing and inventory',
            'icon' => 'package',
            'menu_order' => 20,
            'per_page' => 12,
            'default_sort' => ['name' => 'ASC'],
            'features' => [
                'search' => true,
                'filters' => true,
                'export' => true,
                'bulk_actions' => true,
                'advanced_search' => true,
            ],
        ],
        'Category' => [
            'class' => 'Demo\\Entity\\Category',
            'title' => 'Categories',
            'description' => 'Organize products into hierarchical categories',
            'icon' => 'folder',
            'menu_order' => 30,
            'per_page' => 20,
            'default_sort' => ['sortOrder' => 'ASC', 'name' => 'ASC'],
            'features' => [
                'search' => true,
                'filters' => true,
                'export' => false,
                'bulk_actions' => false,
                'tree_view' => true,
            ],
        ],
    ],

    // Dashboard Widgets Configuration
    'widgets' => [
        'users_count' => [
            'title' => 'Total Users',
            'type' => 'counter',
            'icon' => 'users',
            'color' => 'blue',
            'position' => [1, 1],
            'size' => [3, 2],
            'refresh_interval' => 60,
            'data_source' => 'Demo\\Entity\\User',
            'query' => [],
        ],
        'active_users' => [
            'title' => 'Active Users',
            'type' => 'counter',
            'icon' => 'user-check',
            'color' => 'green',
            'position' => [1, 2],
            'size' => [3, 2],
            'refresh_interval' => 60,
            'data_source' => 'Demo\\Entity\\User',
            'query' => ['isActive' => true],
        ],
        'products_count' => [
            'title' => 'Total Products',
            'type' => 'counter',
            'icon' => 'package',
            'color' => 'purple',
            'position' => [2, 1],
            'size' => [3, 2],
            'refresh_interval' => 120,
            'data_source' => 'Demo\\Entity\\Product',
            'query' => [],
        ],
        'featured_products' => [
            'title' => 'Featured Products',
            'type' => 'counter',
            'icon' => 'star',
            'color' => 'yellow',
            'position' => [2, 2],
            'size' => [3, 2],
            'refresh_interval' => 120,
            'data_source' => 'Demo\\Entity\\Product',
            'query' => ['isFeatured' => true],
        ],
        'categories_count' => [
            'title' => 'Categories',
            'type' => 'counter',
            'icon' => 'folder',
            'color' => 'indigo',
            'position' => [3, 1],
            'size' => [3, 2],
            'refresh_interval' => 300,
            'data_source' => 'Demo\\Entity\\Category',
            'query' => [],
        ],
        'revenue_chart' => [
            'title' => 'Monthly Revenue',
            'type' => 'chart',
            'chart_type' => 'line',
            'icon' => 'trending-up',
            'color' => 'emerald',
            'position' => [3, 2],
            'size' => [6, 4],
            'refresh_interval' => 300,
            'data_source' => 'custom',
            'data_handler' => 'getRevenueData',
        ],
        'recent_products' => [
            'title' => 'Recent Products',
            'type' => 'list',
            'icon' => 'clock',
            'color' => 'gray',
            'position' => [4, 1],
            'size' => [6, 3],
            'refresh_interval' => 180,
            'data_source' => 'Demo\\Entity\\Product',
            'query' => [],
            'order_by' => ['createdAt' => 'DESC'],
            'limit' => 5,
            'template' => 'widgets/recent_products.tpl',
        ],
        'user_roles_pie' => [
            'title' => 'User Roles Distribution',
            'type' => 'chart',
            'chart_type' => 'pie',
            'icon' => 'pie-chart',
            'color' => 'rose',
            'position' => [5, 1],
            'size' => [4, 3],
            'refresh_interval' => 600,
            'data_source' => 'custom',
            'data_handler' => 'getUserRolesData',
        ],
    ],

    // Navigation Menu Configuration
    'menu' => [
        [
            'label' => 'Dashboard',
            'route' => 'demo_dashboard',
            'icon' => 'home',
            'order' => 1,
        ],
        [
            'label' => 'Catalog',
            'icon' => 'shopping-bag',
            'order' => 10,
            'children' => [
                [
                    'label' => 'Products',
                    'route' => 'demo_products',
                    'icon' => 'package',
                    'badge' => 'dynamic:product_count',
                ],
                [
                    'label' => 'Categories',
                    'route' => 'demo_categories',
                    'icon' => 'folder',
                ],
            ],
        ],
        [
            'label' => 'Users',
            'route' => 'demo_users',
            'icon' => 'users',
            'order' => 20,
            'badge' => 'dynamic:user_count',
        ],
        [
            'label' => 'Reports',
            'icon' => 'bar-chart',
            'order' => 30,
            'children' => [
                [
                    'label' => 'Sales Report',
                    'route' => 'demo_reports_sales',
                    'icon' => 'trending-up',
                ],
                [
                    'label' => 'User Analytics',
                    'route' => 'demo_reports_users',
                    'icon' => 'users',
                ],
                [
                    'label' => 'Product Performance',
                    'route' => 'demo_reports_products',
                    'icon' => 'package',
                ],
            ],
        ],
        [
            'label' => 'Settings',
            'icon' => 'settings',
            'order' => 100,
            'children' => [
                [
                    'label' => 'Demo Settings',
                    'route' => 'demo_settings',
                    'icon' => 'cog',
                ],
                [
                    'label' => 'Reset Demo Data',
                    'route' => 'demo_reset',
                    'icon' => 'refresh-cw',
                    'confirm' => 'Are you sure you want to reset all demo data?',
                ],
            ],
        ],
    ],

    // Demo Data Configuration
    'sample_data' => [
        'auto_seed' => true,
        'seed_on_reset' => true,
        'users_count' => 5,
        'categories_count' => 8,
        'products_count' => 15,
        'generate_images' => false,
        'use_faker' => true,
    ],

    // UI Configuration
    'ui' => [
        'theme' => 'demo',
        'brand_name' => 'AdminKit Demo',
        'brand_logo' => '/assets/images/demo-logo.png',
        'favicon' => '/assets/images/favicon.ico',
        'show_demo_banner' => true,
        'demo_banner_text' => 'This is a demonstration of AdminKit features. Data is reset periodically.',
        'pagination_limit' => 15,
        'date_format' => 'Y-m-d H:i:s',
        'currency' => 'USD',
        'currency_symbol' => '$',
        'language' => 'en',
    ],

    // Features Configuration
    'features' => [
        'search' => [
            'enabled' => true,
            'global_search' => true,
            'search_fields' => ['name', 'email', 'description'],
            'min_length' => 2,
            'highlight_results' => true,
        ],
        'filters' => [
            'enabled' => true,
            'quick_filters' => true,
            'advanced_filters' => true,
            'saved_filters' => false,
        ],
        'export' => [
            'enabled' => true,
            'formats' => ['csv', 'xlsx', 'pdf'],
            'max_records' => 1000,
        ],
        'import' => [
            'enabled' => false,
            'formats' => ['csv', 'xlsx'],
            'template_download' => true,
        ],
        'bulk_actions' => [
            'enabled' => true,
            'actions' => ['delete', 'activate', 'deactivate'],
            'confirm_destructive' => true,
        ],
        'audit_log' => [
            'enabled' => false,
            'track_changes' => false,
            'retention_days' => 90,
        ],
    ],

    // Performance Configuration
    'performance' => [
        'caching' => [
            'enabled' => false,
            'driver' => 'array',
            'ttl' => 3600,
        ],
        'pagination' => [
            'default_limit' => 15,
            'max_limit' => 100,
            'show_totals' => true,
        ],
        'lazy_loading' => [
            'enabled' => true,
            'chunk_size' => 50,
        ],
    ],

    // Security Configuration
    'security' => [
        'demo_mode' => true,
        'read_only' => false,
        'allow_delete' => true,
        'allow_create' => true,
        'allow_edit' => true,
        'session_timeout' => 3600,
        'csrf_protection' => true,
    ],

    // API Configuration
    'api' => [
        'enabled' => true,
        'version' => 'v1',
        'rate_limiting' => [
            'enabled' => false,
            'requests_per_minute' => 60,
        ],
        'authentication' => [
            'enabled' => false,
            'method' => 'token',
        ],
    ],

    // Notifications Configuration
    'notifications' => [
        'enabled' => true,
        'channels' => ['web'],
        'real_time' => false,
        'toast_duration' => 5000,
    ],
];
