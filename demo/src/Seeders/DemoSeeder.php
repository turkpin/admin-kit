<?php

declare(strict_types=1);

namespace Demo\Seeders;

use Demo\Entity\User;
use Demo\Entity\Category;
use Demo\Entity\Product;
use Demo\Entity\UserRole;
use Doctrine\ORM\EntityManagerInterface;

class DemoSeeder
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Seed all demo data
     */
    public function seed(): void
    {
        $this->seedUsers();
        $this->seedCategories();
        $this->seedProducts();
        
        $this->entityManager->flush();
    }

    /**
     * Seed demo users
     */
    private function seedUsers(): void
    {
        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@demo.com',
                'password' => password_hash('admin123', PASSWORD_BCRYPT),
                'role' => UserRole::ADMIN,
                'bio' => 'System administrator with full access to all features.',
                'birthDate' => new \DateTime('1985-06-15'),
                'isActive' => true,
            ],
            [
                'name' => 'John Doe',
                'email' => 'john@demo.com',
                'password' => password_hash('user123', PASSWORD_BCRYPT),
                'role' => UserRole::USER,
                'bio' => 'Regular user with standard permissions.',
                'birthDate' => new \DateTime('1990-03-22'),
                'isActive' => true,
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane@demo.com',
                'password' => password_hash('moderator123', PASSWORD_BCRYPT),
                'role' => UserRole::MODERATOR,
                'bio' => 'Content moderator responsible for quality control.',
                'birthDate' => new \DateTime('1988-11-08'),
                'isActive' => true,
            ],
            [
                'name' => 'Bob Wilson',
                'email' => 'bob@demo.com',
                'password' => password_hash('user123', PASSWORD_BCRYPT),
                'role' => UserRole::USER,
                'bio' => 'Product manager focusing on user experience.',
                'birthDate' => new \DateTime('1992-01-30'),
                'isActive' => false,
            ],
            [
                'name' => 'Alice Johnson',
                'email' => 'alice@demo.com',
                'password' => password_hash('user123', PASSWORD_BCRYPT),
                'role' => UserRole::USER,
                'bio' => 'Marketing specialist with creative background.',
                'birthDate' => new \DateTime('1987-07-12'),
                'isActive' => true,
            ],
        ];

        foreach ($users as $userData) {
            $user = new User();
            $user->setName($userData['name'])
                 ->setEmail($userData['email'])
                 ->setPassword($userData['password'])
                 ->setRole($userData['role'])
                 ->setBio($userData['bio'])
                 ->setBirthDate($userData['birthDate'])
                 ->setIsActive($userData['isActive']);

            $this->entityManager->persist($user);
        }
    }

    /**
     * Seed demo categories
     */
    private function seedCategories(): void
    {
        $categories = [
            [
                'name' => 'Electronics',
                'slug' => 'electronics',
                'description' => 'Electronic devices and gadgets',
                'isActive' => true,
                'sortOrder' => 1,
                'children' => [
                    [
                        'name' => 'Smartphones',
                        'slug' => 'smartphones',
                        'description' => 'Mobile phones and accessories',
                        'isActive' => true,
                        'sortOrder' => 1,
                    ],
                    [
                        'name' => 'Laptops',
                        'slug' => 'laptops',
                        'description' => 'Portable computers and notebooks',
                        'isActive' => true,
                        'sortOrder' => 2,
                    ],
                    [
                        'name' => 'Tablets',
                        'slug' => 'tablets',
                        'description' => 'Tablet computers and e-readers',
                        'isActive' => true,
                        'sortOrder' => 3,
                    ],
                ],
            ],
            [
                'name' => 'Clothing',
                'slug' => 'clothing',
                'description' => 'Fashion and apparel',
                'isActive' => true,
                'sortOrder' => 2,
                'children' => [
                    [
                        'name' => 'Men\'s Clothing',
                        'slug' => 'mens-clothing',
                        'description' => 'Clothing for men',
                        'isActive' => true,
                        'sortOrder' => 1,
                    ],
                    [
                        'name' => 'Women\'s Clothing',
                        'slug' => 'womens-clothing',
                        'description' => 'Clothing for women',
                        'isActive' => true,
                        'sortOrder' => 2,
                    ],
                ],
            ],
            [
                'name' => 'Home & Garden',
                'slug' => 'home-garden',
                'description' => 'Home improvement and gardening supplies',
                'isActive' => true,
                'sortOrder' => 3,
            ],
            [
                'name' => 'Books',
                'slug' => 'books',
                'description' => 'Books and educational materials',
                'isActive' => true,
                'sortOrder' => 4,
            ],
            [
                'name' => 'Sports',
                'slug' => 'sports',
                'description' => 'Sports equipment and fitness gear',
                'isActive' => false,
                'sortOrder' => 5,
            ],
        ];

        $createdCategories = [];
        
        foreach ($categories as $categoryData) {
            $category = new Category();
            $category->setName($categoryData['name'])
                     ->setSlug($categoryData['slug'])
                     ->setDescription($categoryData['description'])
                     ->setIsActive($categoryData['isActive'])
                     ->setSortOrder($categoryData['sortOrder']);

            $this->entityManager->persist($category);
            $createdCategories[$categoryData['slug']] = $category;

            // Create children
            if (isset($categoryData['children'])) {
                foreach ($categoryData['children'] as $childData) {
                    $child = new Category();
                    $child->setName($childData['name'])
                          ->setSlug($childData['slug'])
                          ->setDescription($childData['description'])
                          ->setIsActive($childData['isActive'])
                          ->setSortOrder($childData['sortOrder'])
                          ->setParent($category);

                    $this->entityManager->persist($child);
                    $createdCategories[$childData['slug']] = $child;
                }
            }
        }

        // Store categories for product seeding
        $this->categories = $createdCategories;
    }

    private array $categories = [];

    /**
     * Seed demo products
     */
    private function seedProducts(): void
    {
        $products = [
            // Electronics - Smartphones
            [
                'name' => 'iPhone 15 Pro',
                'slug' => 'iphone-15-pro',
                'description' => 'The latest iPhone with advanced features and stunning design.',
                'price' => 999.99,
                'comparePrice' => 1099.99,
                'category' => 'smartphones',
                'sku' => 'APL-IPH15P-128',
                'stock' => 25,
                'weight' => 0.187,
                'isActive' => true,
                'isFeatured' => true,
                'tags' => 'apple, smartphone, premium, 5g',
            ],
            [
                'name' => 'Samsung Galaxy S24',
                'slug' => 'samsung-galaxy-s24',
                'description' => 'Powerful Android smartphone with excellent camera system.',
                'price' => 849.99,
                'comparePrice' => 899.99,
                'category' => 'smartphones',
                'sku' => 'SAM-GS24-256',
                'stock' => 18,
                'weight' => 0.195,
                'isActive' => true,
                'isFeatured' => true,
                'tags' => 'samsung, android, camera, flagship',
            ],
            [
                'name' => 'Google Pixel 8',
                'slug' => 'google-pixel-8',
                'description' => 'Pure Android experience with AI-powered features.',
                'price' => 699.99,
                'comparePrice' => null,
                'category' => 'smartphones',
                'sku' => 'GOO-PIX8-128',
                'stock' => 12,
                'weight' => 0.187,
                'isActive' => true,
                'isFeatured' => false,
                'tags' => 'google, pixel, ai, photography',
            ],

            // Electronics - Laptops
            [
                'name' => 'MacBook Pro 16"',
                'slug' => 'macbook-pro-16',
                'description' => 'Professional laptop for creators and developers.',
                'price' => 2499.99,
                'comparePrice' => 2699.99,
                'category' => 'laptops',
                'sku' => 'APL-MBP16-512',
                'stock' => 8,
                'weight' => 2.15,
                'isActive' => true,
                'isFeatured' => true,
                'tags' => 'apple, macbook, professional, creator',
            ],
            [
                'name' => 'Dell XPS 13',
                'slug' => 'dell-xps-13',
                'description' => 'Ultra-portable laptop with stunning display.',
                'price' => 1299.99,
                'comparePrice' => null,
                'category' => 'laptops',
                'sku' => 'DEL-XPS13-256',
                'stock' => 15,
                'weight' => 1.27,
                'isActive' => true,
                'isFeatured' => false,
                'tags' => 'dell, ultrabook, portable, business',
            ],

            // Clothing - Men's
            [
                'name' => 'Classic Cotton T-Shirt',
                'slug' => 'classic-cotton-tshirt',
                'description' => 'Comfortable cotton t-shirt for everyday wear.',
                'price' => 24.99,
                'comparePrice' => 29.99,
                'category' => 'mens-clothing',
                'sku' => 'CLO-TSH-COT-M',
                'stock' => 45,
                'weight' => 0.2,
                'isActive' => true,
                'isFeatured' => false,
                'tags' => 'cotton, casual, comfortable, basic',
            ],
            [
                'name' => 'Denim Jeans',
                'slug' => 'denim-jeans',
                'description' => 'Classic blue jeans with modern fit.',
                'price' => 79.99,
                'comparePrice' => null,
                'category' => 'mens-clothing',
                'sku' => 'CLO-JEA-DEN-32',
                'stock' => 22,
                'weight' => 0.6,
                'isActive' => true,
                'isFeatured' => true,
                'tags' => 'denim, jeans, casual, classic',
            ],

            // Books
            [
                'name' => 'The Art of Programming',
                'slug' => 'art-of-programming',
                'description' => 'Comprehensive guide to software development.',
                'price' => 49.99,
                'comparePrice' => null,
                'category' => 'books',
                'sku' => 'BOO-ART-PRO-001',
                'stock' => 35,
                'weight' => 0.8,
                'isActive' => true,
                'isFeatured' => false,
                'tags' => 'programming, technology, education, software',
            ],
            [
                'name' => 'Design Patterns',
                'slug' => 'design-patterns',
                'description' => 'Essential patterns for object-oriented design.',
                'price' => 39.99,
                'comparePrice' => 44.99,
                'category' => 'books',
                'sku' => 'BOO-DES-PAT-002',
                'stock' => 28,
                'weight' => 0.7,
                'isActive' => true,
                'isFeatured' => true,
                'tags' => 'design, patterns, programming, architecture',
            ],

            // Home & Garden
            [
                'name' => 'Smart Thermostat',
                'slug' => 'smart-thermostat',
                'description' => 'WiFi-enabled thermostat with energy savings.',
                'price' => 199.99,
                'comparePrice' => 249.99,
                'category' => 'home-garden',
                'sku' => 'HOM-THE-SMA-001',
                'stock' => 14,
                'weight' => 0.5,
                'isActive' => true,
                'isFeatured' => true,
                'tags' => 'smart home, thermostat, energy, wifi',
            ],
        ];

        foreach ($products as $productData) {
            if (!isset($this->categories[$productData['category']])) {
                continue; // Skip if category doesn't exist
            }

            $product = new Product();
            $product->setName($productData['name'])
                    ->setSlug($productData['slug'])
                    ->setDescription($productData['description'])
                    ->setPrice($productData['price'])
                    ->setComparePrice($productData['comparePrice'])
                    ->setCategory($this->categories[$productData['category']])
                    ->setSku($productData['sku'])
                    ->setStock($productData['stock'])
                    ->setWeight($productData['weight'])
                    ->setIsActive($productData['isActive'])
                    ->setIsFeatured($productData['isFeatured'])
                    ->setTags($productData['tags']);

            $this->entityManager->persist($product);
        }
    }

    /**
     * Clear all demo data
     */
    public function clear(): void
    {
        // Delete in reverse order due to foreign key constraints
        $this->entityManager->createQuery('DELETE FROM Demo\Entity\Product')->execute();
        $this->entityManager->createQuery('DELETE FROM Demo\Entity\Category')->execute();
        $this->entityManager->createQuery('DELETE FROM Demo\Entity\User')->execute();
    }

    /**
     * Get seeding statistics
     */
    public function getStats(): array
    {
        return [
            'users' => $this->entityManager->getRepository(User::class)->count([]),
            'categories' => $this->entityManager->getRepository(Category::class)->count([]),
            'products' => $this->entityManager->getRepository(Product::class)->count([]),
        ];
    }
}
