<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Services;

use Doctrine\ORM\EntityManagerInterface;
use Turkpin\AdminKit\Services\AuthService;
use Turkpin\AdminKit\Services\CacheService;

class SearchService
{
    private EntityManagerInterface $entityManager;
    private AuthService $authService;
    private CacheService $cacheService;
    private array $searchableEntities;
    private array $searchConfig;

    public function __construct(
        EntityManagerInterface $entityManager, 
        AuthService $authService,
        CacheService $cacheService,
        array $searchableEntities = []
    ) {
        $this->entityManager = $entityManager;
        $this->authService = $authService;
        $this->cacheService = $cacheService;
        $this->searchableEntities = $searchableEntities;
        $this->searchConfig = [
            'min_search_length' => 2,
            'max_results_per_entity' => 10,
            'cache_ttl' => 300, // 5 minutes
            'highlight_enabled' => true
        ];
    }

    /**
     * Configure search settings
     */
    public function configure(array $config): void
    {
        $this->searchConfig = array_merge($this->searchConfig, $config);
    }

    /**
     * Register searchable entity
     */
    public function registerEntity(string $entityName, array $config): void
    {
        $this->searchableEntities[$entityName] = array_merge([
            'class' => null,
            'title_field' => 'name',
            'description_field' => null,
            'searchable_fields' => ['name'],
            'url_pattern' => '/admin/{entity}/{id}',
            'icon' => 'document',
            'permission' => 'index'
        ], $config);
    }

    /**
     * Perform global search across all entities
     */
    public function globalSearch(string $query, int $limit = 50): array
    {
        if (strlen($query) < $this->searchConfig['min_search_length']) {
            return [];
        }

        $cacheKey = "global_search:" . md5($query . serialize($this->searchableEntities));
        
        return $this->cacheService->remember($cacheKey, function() use ($query, $limit) {
            $results = [];
            $totalFound = 0;

            foreach ($this->searchableEntities as $entityName => $config) {
                if (!$this->canSearchEntity($entityName, $config)) {
                    continue;
                }

                $entityResults = $this->searchEntity($entityName, $config, $query);
                
                if (!empty($entityResults)) {
                    $results[$entityName] = [
                        'entity_name' => $entityName,
                        'entity_config' => $config,
                        'results' => array_slice($entityResults, 0, $this->searchConfig['max_results_per_entity']),
                        'total_found' => count($entityResults),
                        'has_more' => count($entityResults) > $this->searchConfig['max_results_per_entity']
                    ];
                    $totalFound += count($entityResults);
                }

                if ($totalFound >= $limit) {
                    break;
                }
            }

            return [
                'query' => $query,
                'results' => $results,
                'total_entities' => count($results),
                'total_results' => $totalFound,
                'search_time' => microtime(true)
            ];
        }, $this->searchConfig['cache_ttl']);
    }

    /**
     * Search within specific entity
     */
    public function searchEntity(string $entityName, array $config, string $query): array
    {
        if (!isset($config['class'])) {
            return [];
        }

        try {
            $repository = $this->entityManager->getRepository($config['class']);
            $queryBuilder = $repository->createQueryBuilder('e');

            // Build search conditions
            $searchConditions = [];
            $paramCounter = 0;

            foreach ($config['searchable_fields'] as $field) {
                $paramName = "search_param_{$paramCounter}";
                $searchConditions[] = "e.{$field} LIKE :{$paramName}";
                $queryBuilder->setParameter($paramName, "%{$query}%");
                $paramCounter++;
            }

            if (empty($searchConditions)) {
                return [];
            }

            $queryBuilder->where(implode(' OR ', $searchConditions))
                        ->orderBy('e.id', 'DESC')
                        ->setMaxResults(100); // Reasonable limit

            $entities = $queryBuilder->getQuery()->getResult();

            return array_map(function($entity) use ($config, $query) {
                return $this->formatSearchResult($entity, $config, $query);
            }, $entities);

        } catch (\Exception $e) {
            error_log("Search error for entity {$entityName}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Format search result
     */
    private function formatSearchResult($entity, array $config, string $query): array
    {
        $titleField = $config['title_field'];
        $descriptionField = $config['description_field'];

        $title = $this->getFieldValue($entity, $titleField);
        $description = $descriptionField ? $this->getFieldValue($entity, $descriptionField) : null;

        // Highlight search terms
        if ($this->searchConfig['highlight_enabled']) {
            $title = $this->highlightSearchTerm($title, $query);
            if ($description) {
                $description = $this->highlightSearchTerm($description, $query);
            }
        }

        // Generate URL
        $url = str_replace(['{entity}', '{id}'], [
            $config['entity_name'] ?? 'item',
            $entity->getId()
        ], $config['url_pattern']);

        return [
            'id' => $entity->getId(),
            'title' => $title,
            'description' => $description,
            'url' => $url,
            'icon' => $config['icon'],
            'entity_name' => $config['entity_name'] ?? 'item',
            'created_at' => method_exists($entity, 'getCreatedAt') ? $entity->getCreatedAt() : null
        ];
    }

    /**
     * Get field value from entity
     */
    private function getFieldValue($entity, string $field): ?string
    {
        $getter = 'get' . ucfirst($field);
        
        if (method_exists($entity, $getter)) {
            $value = $entity->$getter();
            return is_string($value) ? $value : (string)$value;
        }

        return null;
    }

    /**
     * Highlight search terms in text
     */
    private function highlightSearchTerm(string $text, string $query): string
    {
        if (empty($query) || empty($text)) {
            return $text;
        }

        // Split query into words
        $words = preg_split('/\s+/', trim($query));
        
        foreach ($words as $word) {
            if (strlen($word) >= 2) {
                $pattern = '/(' . preg_quote($word, '/') . ')/i';
                $text = preg_replace($pattern, '<mark class="bg-yellow-200">$1</mark>', $text);
            }
        }

        return $text;
    }

    /**
     * Check if user can search entity
     */
    private function canSearchEntity(string $entityName, array $config): bool
    {
        $permission = $config['permission'] ?? 'index';
        
        try {
            return $this->authService->canAccess($entityName, $permission);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get search suggestions
     */
    public function getSuggestions(string $query, int $limit = 10): array
    {
        if (strlen($query) < 2) {
            return [];
        }

        // Get recent search terms from cache/database
        $suggestions = $this->getPopularSearchTerms($query, $limit);
        
        return array_map(function($term) {
            return [
                'value' => $term,
                'label' => $term
            ];
        }, $suggestions);
    }

    /**
     * Get popular search terms
     */
    private function getPopularSearchTerms(string $query, int $limit): array
    {
        // In a real implementation, you would store search history
        // For now, return some example suggestions
        $commonTerms = [
            'admin', 'user', 'role', 'permission', 'setting', 
            'dashboard', 'export', 'import', 'report', 'log'
        ];

        return array_filter($commonTerms, function($term) use ($query) {
            return stripos($term, $query) !== false;
        });
    }

    /**
     * Log search query for analytics
     */
    public function logSearch(string $query, int $resultsCount): void
    {
        try {
            $user = $this->authService->getCurrentUser();
            $userId = $user ? $user->getId() : null;

            // In a real implementation, you would store this in a search_logs table
            $logData = [
                'user_id' => $userId,
                'query' => $query,
                'results_count' => $resultsCount,
                'searched_at' => new \DateTime(),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
            ];

            // Store in cache for now
            $this->cacheService->set(
                "search_log:" . time() . "_" . $userId,
                $logData,
                86400 // 1 day
            );

        } catch (\Exception $e) {
            // Don't let logging errors break search
            error_log("Search logging error: " . $e->getMessage());
        }
    }

    /**
     * Get search statistics
     */
    public function getSearchStats(): array
    {
        // In a real implementation, this would query the search_logs table
        return [
            'total_searches' => 0,
            'unique_queries' => 0,
            'avg_results_per_search' => 0,
            'most_popular_queries' => [],
            'search_trends' => []
        ];
    }

    /**
     * Render search interface
     */
    public function renderSearchUI(): string
    {
        return '
        <div class="relative">
            <div class="relative">
                <input type="text" 
                       id="global-search" 
                       name="q"
                       class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                       placeholder="Search..."
                       autocomplete="off">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
            </div>
            
            <div id="search-results" class="absolute z-50 mt-1 w-full bg-white rounded-md shadow-lg border border-gray-200 hidden max-h-96 overflow-y-auto">
                <div id="search-loading" class="hidden p-4 text-center text-gray-500">
                    <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-500 mx-auto"></div>
                    <p class="mt-2">Searching...</p>
                </div>
                
                <div id="search-content"></div>
                
                <div id="search-empty" class="hidden p-4 text-center text-gray-500">
                    <p>No results found</p>
                </div>
            </div>
        </div>

        <script>
        class GlobalSearch {
            constructor() {
                this.searchInput = document.getElementById("global-search");
                this.searchResults = document.getElementById("search-results");
                this.searchContent = document.getElementById("search-content");
                this.searchLoading = document.getElementById("search-loading");
                this.searchEmpty = document.getElementById("search-empty");
                this.searchTimeout = null;
                this.minSearchLength = 2;
                
                this.init();
            }
            
            init() {
                if (!this.searchInput) return;
                
                this.searchInput.addEventListener("input", (e) => {
                    this.handleSearchInput(e.target.value);
                });
                
                this.searchInput.addEventListener("focus", () => {
                    if (this.searchInput.value.length >= this.minSearchLength) {
                        this.showResults();
                    }
                });
                
                // Hide results when clicking outside
                document.addEventListener("click", (e) => {
                    if (!this.searchInput.contains(e.target) && !this.searchResults.contains(e.target)) {
                        this.hideResults();
                    }
                });
                
                // Handle keyboard navigation
                this.searchInput.addEventListener("keydown", (e) => {
                    this.handleKeyNavigation(e);
                });
            }
            
            handleSearchInput(query) {
                clearTimeout(this.searchTimeout);
                
                if (query.length < this.minSearchLength) {
                    this.hideResults();
                    return;
                }
                
                this.searchTimeout = setTimeout(() => {
                    this.performSearch(query);
                }, 300); // Debounce
            }
            
            async performSearch(query) {
                this.showLoading();
                
                try {
                    const response = await fetch(`/admin/search?q=${encodeURIComponent(query)}`, {
                        method: "GET",
                        headers: {
                            "X-Requested-With": "XMLHttpRequest"
                        }
                    });
                    
                    if (!response.ok) {
                        throw new Error("Search request failed");
                    }
                    
                    const data = await response.json();
                    this.displayResults(data);
                    
                } catch (error) {
                    console.error("Search error:", error);
                    this.showError("Search failed. Please try again.");
                }
            }
            
            displayResults(data) {
                this.hideLoading();
                
                if (!data.results || Object.keys(data.results).length === 0) {
                    this.showEmpty();
                    return;
                }
                
                let html = "";
                
                for (const [entityName, entityData] of Object.entries(data.results)) {
                    html += `<div class="border-b border-gray-100 last:border-b-0">`;
                    html += `<div class="px-4 py-2 bg-gray-50 text-sm font-medium text-gray-700">${entityName}</div>`;
                    
                    entityData.results.forEach(result => {
                        html += `
                        <a href="${result.url}" class="block px-4 py-3 hover:bg-gray-50 border-b border-gray-100 last:border-b-0">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="icon-${result.icon} text-gray-400"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900">${result.title}</p>
                                    ${result.description ? `<p class="text-sm text-gray-500">${result.description}</p>` : ""}
                                </div>
                            </div>
                        </a>`;
                    });
                    
                    if (entityData.has_more) {
                        html += `<div class="px-4 py-2 text-xs text-gray-500">+${entityData.total_found - entityData.results.length} more results</div>`;
                    }
                    
                    html += "</div>";
                }
                
                this.searchContent.innerHTML = html;
                this.showResults();
            }
            
            showLoading() {
                this.searchLoading.classList.remove("hidden");
                this.searchContent.innerHTML = "";
                this.searchEmpty.classList.add("hidden");
                this.showResults();
            }
            
            hideLoading() {
                this.searchLoading.classList.add("hidden");
            }
            
            showEmpty() {
                this.searchEmpty.classList.remove("hidden");
                this.searchContent.innerHTML = "";
                this.showResults();
            }
            
            showError(message) {
                this.hideLoading();
                this.searchContent.innerHTML = `<div class="p-4 text-red-600">${message}</div>`;
                this.showResults();
            }
            
            showResults() {
                this.searchResults.classList.remove("hidden");
            }
            
            hideResults() {
                this.searchResults.classList.add("hidden");
            }
            
            handleKeyNavigation(e) {
                // Handle arrow keys, enter, escape
                if (e.key === "Escape") {
                    this.hideResults();
                    this.searchInput.blur();
                }
            }
        }
        
        // Initialize when DOM is ready
        document.addEventListener("DOMContentLoaded", () => {
            new GlobalSearch();
        });
        </script>';
    }
}
