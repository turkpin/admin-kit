# Basic Setup Example

This example shows how to set up AdminKit with a simple blog application.

## 1. Project Structure

```
my-blog-admin/
├── public/
│   ├── index.php
│   └── .htaccess
├── src/
│   └── Entity/
│       ├── Post.php
│       ├── Category.php
│       └── User.php
├── config/
│   ├── container.php
│   ├── doctrine.php
│   └── smarty.php
├── bootstrap/
│   └── app.php
├── .env
└── composer.json
```

## 2. Install Dependencies

```bash
composer require turkpin/admin-kit
```

## 3. Create Entities

### Post Entity

```php
<?php
// src/Entity/Post.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'posts')]
class Post
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $slug;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $excerpt = null;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Category $category;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $author;

    #[ORM\Column(type: 'string', length: 50)]
    private string $status = 'draft';

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $publishedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    // Getters and setters...

    public function __toString(): string
    {
        return $this->title;
    }
}
```

### Category Entity

```php
<?php
// src/Entity/Category.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ORM\Table(name: 'categories')]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $slug;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\OneToMany(mappedBy: 'category', targetEntity: Post::class)]
    private Collection $posts;

    public function __construct()
    {
        $this->posts = new ArrayCollection();
    }

    // Getters and setters...

    public function __toString(): string
    {
        return $this->name;
    }
}
```

## 4. Configure AdminKit

```php
<?php
// public/index.php

require_once __DIR__ . '/../vendor/autoload.php';

use AdminKit\AdminKit;
use Slim\Factory\AppFactory;

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Create Slim app
$app = AppFactory::create();

// Initialize AdminKit
$adminKit = new AdminKit($app, [
    'route_prefix' => '/admin',
    'brand_name' => 'Blog Admin',
    'locale' => 'en',
]);

// Configure Post entity
$adminKit->addEntity(\App\Entity\Post::class, [
    'title' => 'Posts',
    'description' => 'Manage blog posts',
    'icon' => 'file-text',
    'fields' => [
        'title' => [
            'type' => 'text',
            'label' => 'Title',
            'required' => true,
        ],
        'slug' => [
            'type' => 'text',
            'label' => 'URL Slug',
            'help' => 'Auto-generated from title if empty',
        ],
        'excerpt' => [
            'type' => 'textarea',
            'label' => 'Excerpt',
            'rows' => 3,
            'help' => 'Short description for previews',
        ],
        'content' => [
            'type' => 'textarea',
            'label' => 'Content',
            'rows' => 10,
            'required' => true,
        ],
        'category' => [
            'type' => 'association',
            'label' => 'Category',
            'target_entity' => \App\Entity\Category::class,
            'choice_label' => 'name',
            'required' => true,
        ],
        'author' => [
            'type' => 'association',
            'label' => 'Author',
            'target_entity' => \App\Entity\User::class,
            'choice_label' => 'name',
            'required' => true,
        ],
        'status' => [
            'type' => 'choice',
            'label' => 'Status',
            'choices' => [
                'draft' => 'Draft',
                'published' => 'Published',
                'archived' => 'Archived',
            ],
            'default' => 'draft',
        ],
        'publishedAt' => [
            'type' => 'datetime',
            'label' => 'Publish Date',
        ],
    ],
    'filters' => ['category', 'author', 'status'],
    'search' => ['title', 'content', 'excerpt'],
    'actions' => ['index', 'show', 'new', 'edit', 'delete'],
]);

// Configure Category entity
$adminKit->addEntity(\App\Entity\Category::class, [
    'title' => 'Categories',
    'description' => 'Organize posts into categories',
    'icon' => 'folder',
    'fields' => [
        'name' => [
            'type' => 'text',
            'label' => 'Name',
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
    ],
    'search' => ['name', 'description'],
    'actions' => ['index', 'show', 'new', 'edit', 'delete'],
]);

// Configure User entity
$adminKit->addEntity(\App\Entity\User::class, [
    'title' => 'Users',
    'description' => 'Manage blog authors',
    'icon' => 'users',
    'fields' => [
        'name' => [
            'type' => 'text',
            'label' => 'Full Name',
            'required' => true,
        ],
        'email' => [
            'type' => 'email',
            'label' => 'Email',
            'required' => true,
        ],
        'password' => [
            'type' => 'password',
            'label' => 'Password',
            'required' => true,
        ],
        'bio' => [
            'type' => 'textarea',
            'label' => 'Biography',
            'rows' => 4,
        ],
        'isActive' => [
            'type' => 'boolean',
            'label' => 'Active',
            'default' => true,
        ],
    ],
    'filters' => ['isActive'],
    'search' => ['name', 'email'],
    'actions' => ['index', 'show', 'new', 'edit', 'delete'],
]);

// Add dashboard widgets
$adminKit->addDashboardWidget('total_posts', [
    'title' => 'Total Posts',
    'type' => 'counter',
    'value' => function() use ($adminKit) {
        $em = $adminKit->getEntityManager();
        return $em->getRepository(\App\Entity\Post::class)->count([]);
    },
    'icon' => 'file-text',
    'color' => 'blue',
    'link' => '/admin/post',
]);

$adminKit->addDashboardWidget('published_posts', [
    'title' => 'Published Posts',
    'type' => 'counter',
    'value' => function() use ($adminKit) {
        $em = $adminKit->getEntityManager();
        return $em->getRepository(\App\Entity\Post::class)->count(['status' => 'published']);
    },
    'icon' => 'check-circle',
    'color' => 'green',
]);

$adminKit->addDashboardWidget('draft_posts', [
    'title' => 'Draft Posts',
    'type' => 'counter',
    'value' => function() use ($adminKit) {
        $em = $adminKit->getEntityManager();
        return $em->getRepository(\App\Entity\Post::class)->count(['status' => 'draft']);
    },
    'icon' => 'edit',
    'color' => 'yellow',
]);

$adminKit->addDashboardWidget('categories', [
    'title' => 'Categories',
    'type' => 'counter',
    'value' => function() use ($adminKit) {
        $em = $adminKit->getEntityManager();
        return $em->getRepository(\App\Entity\Category::class)->count([]);
    },
    'icon' => 'folder',
    'color' => 'purple',
    'link' => '/admin/category',
]);

// Run the application
$app->run();
```

## 5. Environment Configuration

```env
# .env
APP_NAME="Blog Admin"
APP_URL=http://localhost:8000
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_LOCALE=en

DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=blog_admin
DB_USERNAME=root
DB_PASSWORD=

ADMINKIT_ROUTE_PREFIX=/admin
ADMINKIT_BRAND_NAME="Blog Admin"

CACHE_ENABLED=true
SMARTY_CACHING=false
```

## 6. Database Setup

```bash
# Run migrations
php bin/adminkit migrate

# Create admin user
php bin/adminkit user:create --admin admin@blog.com password123 "Admin User"
```

## 7. Start Development Server

```bash
php bin/adminkit serve
```

Visit `http://localhost:8000/admin` to access your blog admin panel!

## 8. Customization Options

### Custom Dashboard Widget

```php
$adminKit->addDashboardWidget('recent_posts', [
    'title' => 'Recent Posts',
    'type' => 'list',
    'template' => 'widgets/recent_posts.tpl',
    'data' => function() use ($adminKit) {
        $em = $adminKit->getEntityManager();
        return $em->getRepository(\App\Entity\Post::class)
            ->findBy([], ['createdAt' => 'DESC'], 5);
    },
]);
```

### Custom Action

```php
// Add custom action to posts
$adminKit->addEntity(\App\Entity\Post::class, [
    // ... other configuration
    'custom_actions' => [
        'publish' => [
            'label' => 'Publish',
            'icon' => 'globe',
            'handler' => function($post) {
                $post->setStatus('published');
                $post->setPublishedAt(new \DateTime());
                // Save changes
                return 'Post published successfully!';
            },
        ],
    ],
]);
```

### Menu Customization

```php
$adminKit->configureMenu([
    [
        'label' => 'Dashboard',
        'route' => 'dashboard',
        'icon' => 'home',
    ],
    [
        'label' => 'Content',
        'icon' => 'edit',
        'children' => [
            [
                'label' => 'Posts',
                'route' => 'posts_index',
                'icon' => 'file-text',
            ],
            [
                'label' => 'Categories',
                'route' => 'categories_index',
                'icon' => 'folder',
            ],
        ],
    ],
    [
        'label' => 'Users',
        'route' => 'users_index',
        'icon' => 'users',
        'permission' => 'admin',
    ],
]);
```

This example provides a complete, functional blog admin panel with AdminKit!
