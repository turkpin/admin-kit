<?php
/**
 * AdminKit Demo
 * Bu dosya AdminKit'in nasıl kullanılacağını gösterir
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Smarty;
use Turkpin\AdminKit\AdminKit;

// Slim App oluştur
$app = AppFactory::create();

// Doctrine EntityManager kurulumu (örnek)
// $entityManager = setupDoctrine();

// Smarty kurulumu
$smarty = new Smarty();

// AdminKit'i başlat
$adminKit = new AdminKit($app, [
    'doctrine' => $entityManager, // Doctrine EntityManager
    'smarty' => $smarty,
    'route_prefix' => '/admin',
    'brand_name' => 'Demo Admin',
    'auth_required' => true,
    'rbac_enabled' => true,
]);

// User entity'sini ekle
$adminKit->addEntity(\App\Entity\User::class, [
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
        'password' => [
            'type' => 'password',
            'label' => 'Şifre',
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
            'target_entity' => \App\Entity\Role::class,
            'multiple' => true
        ]
    ],
    'filters' => ['name', 'email', 'isActive'],
    'actions' => ['index', 'show', 'new', 'edit', 'delete'],
    'permissions' => ['admin', 'user_manager']
]);

// Product entity'sini ekle
$adminKit->addEntity(\App\Entity\Product::class, [
    'title' => 'Ürünler',
    'fields' => [
        'name' => [
            'type' => 'text',
            'label' => 'Ürün Adı',
            'required' => true
        ],
        'price' => [
            'type' => 'money',
            'label' => 'Fiyat',
            'currency' => 'TL'
        ],
        'description' => [
            'type' => 'textarea',
            'label' => 'Açıklama'
        ],
        'image' => [
            'type' => 'image',
            'label' => 'Ürün Resmi',
            'upload_dir' => 'uploads/products/'
        ],
        'isActive' => [
            'type' => 'boolean',
            'label' => 'Aktif'
        ],
        'category' => [
            'type' => 'association',
            'label' => 'Kategori',
            'target_entity' => \App\Entity\Category::class
        ]
    ],
    'filters' => ['name', 'isActive', 'category'],
    'actions' => ['index', 'show', 'new', 'edit', 'delete']
]);

// Dashboard widget'ları ekle
$adminKit->addDashboardWidget('users_count', [
    'title' => 'Toplam Kullanıcılar',
    'value' => function() use ($entityManager) {
        return $entityManager->getRepository(\App\Entity\User::class)->count([]);
    },
    'icon' => 'users',
    'color' => 'blue'
]);

$adminKit->addDashboardWidget('active_users', [
    'title' => 'Aktif Kullanıcılar',
    'value' => function() use ($entityManager) {
        return $entityManager->getRepository(\App\Entity\User::class)->count(['isActive' => true]);
    },
    'icon' => 'user',
    'color' => 'green'
]);

$adminKit->addDashboardWidget('products_count', [
    'title' => 'Toplam Ürünler',
    'value' => function() use ($entityManager) {
        return $entityManager->getRepository(\App\Entity\Product::class)->count([]);
    },
    'icon' => 'chart-bar',
    'color' => 'purple'
]);

$adminKit->addDashboardWidget('revenue', [
    'title' => 'Bu Ay Gelir',
    'value' => function() {
        // Bu örnekte sabit değer döndürüyoruz
        return '₺25,430';
    },
    'icon' => 'chart-bar',
    'color' => 'green'
]);

// Özel route ekle
$adminKit->addCustomRoute('GET', '/reports', function($request, $response) {
    $response->getBody()->write('Raporlar sayfası');
    return $response;
});

// Örnek kullanıcı, rol ve permission oluşturma
/*
$authService = $adminKit->getAuthService();

// Permissions oluştur
$userIndexPerm = $authService->createPermission('user.index', 'Kullanıcıları görüntüle', 'user', 'index');
$userCreatePerm = $authService->createPermission('user.create', 'Kullanıcı oluştur', 'user', 'create');
$userEditPerm = $authService->createPermission('user.edit', 'Kullanıcı düzenle', 'user', 'edit');
$userDeletePerm = $authService->createPermission('user.delete', 'Kullanıcı sil', 'user', 'delete');

$productIndexPerm = $authService->createPermission('product.index', 'Ürünleri görüntüle', 'product', 'index');
$productCreatePerm = $authService->createPermission('product.create', 'Ürün oluştur', 'product', 'create');

// Roller oluştur
$adminRole = $authService->createRole('admin', 'Administrator');
$editorRole = $authService->createRole('editor', 'Editor');

// Permission'ları rollere ata
$authService->assignPermissionToRole($adminRole, $userIndexPerm);
$authService->assignPermissionToRole($adminRole, $userCreatePerm);
$authService->assignPermissionToRole($adminRole, $userEditPerm);
$authService->assignPermissionToRole($adminRole, $userDeletePerm);
$authService->assignPermissionToRole($adminRole, $productIndexPerm);
$authService->assignPermissionToRole($adminRole, $productCreatePerm);

$authService->assignPermissionToRole($editorRole, $productIndexPerm);
$authService->assignPermissionToRole($editorRole, $productCreatePerm);

// Admin kullanıcı oluştur
$adminUser = $authService->createUser([
    'name' => 'Admin User',
    'email' => 'admin@example.com',
    'password' => 'admin123'
]);

$authService->assignRole($adminUser, $adminRole);
*/

// Uygulamayı çalıştır
$app->run();
