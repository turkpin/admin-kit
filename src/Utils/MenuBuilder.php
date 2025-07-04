<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Utils;

class MenuBuilder
{
    private array $items = [];
    private array $config = [];
    private ?object $currentUser = null;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'css_class' => 'admin-menu',
            'active_class' => 'active',
            'submenu_class' => 'submenu',
            'icon_class' => 'menu-icon',
            'show_icons' => true,
            'collapsible' => true,
            'current_url' => $_SERVER['REQUEST_URI'] ?? ''
        ], $config);
    }

    /**
     * Set current user for permission checks
     */
    public function setCurrentUser(?object $currentUser): self
    {
        $this->currentUser = $currentUser;
        return $this;
    }

    /**
     * Add menu item
     */
    public function addItem(string $name, array $options = []): self
    {
        $this->items[$name] = array_merge([
            'label' => $name,
            'url' => '#',
            'icon' => null,
            'permission' => null,
            'roles' => [],
            'badge' => null,
            'badge_color' => 'blue',
            'target' => null,
            'children' => [],
            'order' => 100,
            'visible' => true,
            'active' => false
        ], $options);

        return $this;
    }

    /**
     * Add submenu item
     */
    public function addSubItem(string $parentName, string $name, array $options = []): self
    {
        if (!isset($this->items[$parentName])) {
            throw new \InvalidArgumentException("Parent menu item '{$parentName}' does not exist.");
        }

        $this->items[$parentName]['children'][$name] = array_merge([
            'label' => $name,
            'url' => '#',
            'icon' => null,
            'permission' => null,
            'roles' => [],
            'badge' => null,
            'badge_color' => 'blue',
            'target' => null,
            'order' => 100,
            'visible' => true,
            'active' => false
        ], $options);

        return $this;
    }

    /**
     * Remove menu item
     */
    public function removeItem(string $name): self
    {
        unset($this->items[$name]);
        return $this;
    }

    /**
     * Set item order
     */
    public function setOrder(string $name, int $order): self
    {
        if (isset($this->items[$name])) {
            $this->items[$name]['order'] = $order;
        }
        return $this;
    }

    /**
     * Add dashboard menu
     */
    public function addDashboard(string $url = '/admin'): self
    {
        return $this->addItem('dashboard', [
            'label' => 'Dashboard',
            'url' => $url,
            'icon' => 'home',
            'order' => 1
        ]);
    }

    /**
     * Add entity CRUD menu
     */
    public function addEntity(string $name, array $entityConfig): self
    {
        $label = $entityConfig['label'] ?? ucfirst($name);
        $baseUrl = '/admin/' . $name;
        
        $this->addItem($name, [
            'label' => $label,
            'url' => $baseUrl,
            'icon' => $entityConfig['icon'] ?? 'table',
            'permission' => $entityConfig['permission'] ?? null,
            'roles' => $entityConfig['roles'] ?? [],
            'order' => $entityConfig['order'] ?? 50
        ]);

        // Add sub-items for actions
        if (in_array('list', $entityConfig['actions'] ?? [])) {
            $this->addSubItem($name, 'list', [
                'label' => 'Liste',
                'url' => $baseUrl,
                'icon' => 'list'
            ]);
        }

        if (in_array('new', $entityConfig['actions'] ?? [])) {
            $this->addSubItem($name, 'new', [
                'label' => 'Yeni Ekle',
                'url' => $baseUrl . '/new',
                'icon' => 'plus'
            ]);
        }

        return $this;
    }

    /**
     * Add separator
     */
    public function addSeparator(string $name = null): self
    {
        $name = $name ?: 'separator_' . uniqid();
        
        return $this->addItem($name, [
            'type' => 'separator',
            'label' => '',
            'url' => '',
            'visible' => true
        ]);
    }

    /**
     * Add custom section
     */
    public function addSection(string $name, string $label, array $items = []): self
    {
        $this->addItem($name, [
            'type' => 'section',
            'label' => $label,
            'url' => '',
            'children' => $items
        ]);

        return $this;
    }

    /**
     * Render complete menu
     */
    public function render(): string
    {
        $visibleItems = $this->getVisibleItems();
        $sortedItems = $this->sortItems($visibleItems);

        $html = '<nav class="' . htmlspecialchars($this->config['css_class']) . '">';
        $html .= '<ul class="menu-list">';

        foreach ($sortedItems as $name => $item) {
            $html .= $this->renderItem($name, $item);
        }

        $html .= '</ul>';
        $html .= '</nav>';

        // Add JavaScript for interactive features
        $html .= $this->renderJavaScript();

        return $html;
    }

    /**
     * Render single menu item
     */
    protected function renderItem(string $name, array $item): string
    {
        // Handle separators
        if (($item['type'] ?? '') === 'separator') {
            return '<li class="menu-separator"><hr class="border-gray-300 my-2"></li>';
        }

        // Handle sections
        if (($item['type'] ?? '') === 'section') {
            return $this->renderSection($name, $item);
        }

        $isActive = $this->isItemActive($item);
        $hasChildren = !empty($item['children']);

        $html = '<li class="menu-item' . ($isActive ? ' ' . $this->config['active_class'] : '') . 
                ($hasChildren ? ' has-children' : '') . '">';

        // Main item link
        $html .= '<a href="' . htmlspecialchars($item['url']) . '" ';
        $html .= 'class="menu-link flex items-center px-4 py-2 hover:bg-gray-100 transition-colors" ';
        
        if ($item['target']) {
            $html .= 'target="' . htmlspecialchars($item['target']) . '" ';
        }
        
        if ($hasChildren && $this->config['collapsible']) {
            $html .= 'onclick="toggleSubmenu(event, \'' . htmlspecialchars($name) . '\')" ';
        }
        
        $html .= '>';

        // Icon
        if ($this->config['show_icons'] && $item['icon']) {
            $html .= '<span class="' . $this->config['icon_class'] . ' mr-3">';
            $html .= $this->renderIcon($item['icon']);
            $html .= '</span>';
        }

        // Label
        $html .= '<span class="menu-label flex-1">' . htmlspecialchars($item['label']) . '</span>';

        // Badge
        if ($item['badge']) {
            $html .= '<span class="badge bg-' . $item['badge_color'] . '-500 text-white text-xs px-2 py-1 rounded-full ml-2">';
            $html .= htmlspecialchars($item['badge']);
            $html .= '</span>';
        }

        // Submenu indicator
        if ($hasChildren) {
            $html .= '<span class="submenu-indicator ml-2 text-gray-400">▼</span>';
        }

        $html .= '</a>';

        // Render children
        if ($hasChildren) {
            $html .= $this->renderSubmenu($item['children']);
        }

        $html .= '</li>';

        return $html;
    }

    /**
     * Render section
     */
    protected function renderSection(string $name, array $section): string
    {
        $html = '<li class="menu-section">';
        $html .= '<div class="section-header px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">';
        $html .= htmlspecialchars($section['label']);
        $html .= '</div>';
        
        if (!empty($section['children'])) {
            $html .= '<ul class="section-items">';
            foreach ($section['children'] as $itemName => $item) {
                if ($this->canUserAccess($item)) {
                    $html .= $this->renderItem($itemName, $item);
                }
            }
            $html .= '</ul>';
        }
        
        $html .= '</li>';

        return $html;
    }

    /**
     * Render submenu
     */
    protected function renderSubmenu(array $children): string
    {
        $html = '<ul class="' . $this->config['submenu_class'] . ' ml-6 hidden">';
        
        $visibleChildren = array_filter($children, [$this, 'canUserAccess']);
        $sortedChildren = $this->sortItems($visibleChildren);

        foreach ($sortedChildren as $name => $child) {
            $html .= $this->renderItem($name, $child);
        }

        $html .= '</ul>';

        return $html;
    }

    /**
     * Render icon
     */
    protected function renderIcon(string $icon): string
    {
        // Support for different icon formats
        if (strpos($icon, '<') === 0) {
            // Raw HTML/SVG
            return $icon;
        } elseif (strpos($icon, 'fa-') === 0) {
            // Font Awesome
            return '<i class="fas ' . htmlspecialchars($icon) . '"></i>';
        } else {
            // Simple emoji/text icons
            $iconMap = [
                'home' => '🏠',
                'users' => '👥',
                'settings' => '⚙️',
                'table' => '📊',
                'list' => '📋',
                'plus' => '➕',
                'edit' => '✏️',
                'delete' => '🗑️',
                'view' => '👁️',
                'chart' => '📈',
                'file' => '📄',
                'folder' => '📁',
                'image' => '🖼️',
                'mail' => '✉️',
                'calendar' => '📅',
                'clock' => '🕒',
                'star' => '⭐',
                'heart' => '❤️',
                'lock' => '🔒',
                'unlock' => '🔓',
                'key' => '🔑',
                'search' => '🔍',
                'filter' => '🔽',
                'sort' => '↕️',
                'upload' => '⬆️',
                'download' => '⬇️'
            ];

            return $iconMap[$icon] ?? '•';
        }
    }

    /**
     * Check if item is active
     */
    protected function isItemActive(array $item): bool
    {
        if ($item['active']) {
            return true;
        }

        $currentUrl = $this->config['current_url'];
        $itemUrl = $item['url'];

        // Exact match
        if ($currentUrl === $itemUrl) {
            return true;
        }

        // Starts with match (for parent pages)
        if ($itemUrl !== '/' && $itemUrl !== '#' && strpos($currentUrl, $itemUrl) === 0) {
            return true;
        }

        return false;
    }

    /**
     * Get visible items based on permissions
     */
    protected function getVisibleItems(): array
    {
        return array_filter($this->items, function($item) {
            return $item['visible'] && $this->canUserAccess($item);
        });
    }

    /**
     * Check if user can access item
     */
    protected function canUserAccess(array $item): bool
    {
        // Always visible if no restrictions
        if (!$item['permission'] && empty($item['roles'])) {
            return true;
        }

        // Check if user is set
        if (!$this->currentUser) {
            return false;
        }

        // Check permission
        if ($item['permission'] && method_exists($this->currentUser, 'hasPermission')) {
            if (!$this->currentUser->hasPermission($item['permission'])) {
                return false;
            }
        }

        // Check roles
        if (!empty($item['roles']) && method_exists($this->currentUser, 'hasRole')) {
            $hasRole = false;
            foreach ($item['roles'] as $role) {
                if ($this->currentUser->hasRole($role)) {
                    $hasRole = true;
                    break;
                }
            }
            if (!$hasRole) {
                return false;
            }
        }

        return true;
    }

    /**
     * Sort items by order
     */
    protected function sortItems(array $items): array
    {
        uasort($items, function($a, $b) {
            return ($a['order'] ?? 100) <=> ($b['order'] ?? 100);
        });

        return $items;
    }

    /**
     * Render JavaScript for interactive features
     */
    protected function renderJavaScript(): string
    {
        if (!$this->config['collapsible']) {
            return '';
        }

        return '<script>
        function toggleSubmenu(event, itemName) {
            event.preventDefault();
            const menuItem = event.target.closest(".menu-item");
            const submenu = menuItem.querySelector(".submenu");
            const indicator = menuItem.querySelector(".submenu-indicator");
            
            if (submenu) {
                submenu.classList.toggle("hidden");
                if (indicator) {
                    indicator.textContent = submenu.classList.contains("hidden") ? "▼" : "▲";
                }
            }
        }

        // Auto-expand active menu items
        document.addEventListener("DOMContentLoaded", function() {
            const activeItems = document.querySelectorAll(".menu-item.active");
            activeItems.forEach(item => {
                const submenu = item.querySelector(".submenu");
                const indicator = item.querySelector(".submenu-indicator");
                if (submenu) {
                    submenu.classList.remove("hidden");
                    if (indicator) {
                        indicator.textContent = "▲";
                    }
                }
            });
        });
        </script>';
    }

    /**
     * Create menu from configuration
     */
    public static function fromConfig(array $config, array $entities = []): self
    {
        $builder = new self($config['menu'] ?? []);

        // Add dashboard
        $builder->addDashboard($config['route_prefix'] ?? '/admin');

        // Add separator
        $builder->addSeparator();

        // Add entities
        foreach ($entities as $name => $entityConfig) {
            $builder->addEntity($name, $entityConfig);
        }

        // Add system section
        $builder->addSection('system', 'Sistem', [
            'settings' => [
                'label' => 'Ayarlar',
                'url' => '/admin/settings',
                'icon' => 'settings',
                'roles' => ['admin']
            ],
            'users' => [
                'label' => 'Kullanıcılar',
                'url' => '/admin/users',
                'icon' => 'users',
                'roles' => ['admin']
            ]
        ]);

        return $builder;
    }

    /**
     * Get breadcrumbs from current URL
     */
    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [];
        $currentUrl = $this->config['current_url'];
        $pathParts = explode('/', trim($currentUrl, '/'));

        $breadcrumbs[] = ['title' => 'Ana Sayfa', 'url' => '/'];

        $currentPath = '';
        foreach ($pathParts as $part) {
            if (empty($part)) continue;
            
            $currentPath .= '/' . $part;
            $title = ucfirst(str_replace('-', ' ', $part));
            
            // Try to find menu item for better title
            foreach ($this->items as $item) {
                if ($item['url'] === $currentPath) {
                    $title = $item['label'];
                    break;
                }
            }
            
            $breadcrumbs[] = ['title' => $title, 'url' => $currentPath];
        }

        return $breadcrumbs;
    }

    /**
     * Get all items
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Set item badge
     */
    public function setBadge(string $name, string $badge, string $color = 'blue'): self
    {
        if (isset($this->items[$name])) {
            $this->items[$name]['badge'] = $badge;
            $this->items[$name]['badge_color'] = $color;
        }
        return $this;
    }

    /**
     * Highlight active item
     */
    public function setActive(string $name): self
    {
        if (isset($this->items[$name])) {
            $this->items[$name]['active'] = true;
        }
        return $this;
    }
}
