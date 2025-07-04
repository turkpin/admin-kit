<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Services;

interface CacheAdapterInterface
{
    public function get(string $key): mixed;
    public function set(string $key, mixed $value, int $ttl = 3600): bool;
    public function delete(string $key): bool;
    public function clear(): bool;
    public function exists(string $key): bool;
}

class FileCacheAdapter implements CacheAdapterInterface
{
    private string $cacheDir;

    public function __construct(string $cacheDir = null)
    {
        $this->cacheDir = $cacheDir ?: sys_get_temp_dir() . '/adminkit_cache';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    public function get(string $key): mixed
    {
        $file = $this->getFilePath($key);
        if (!file_exists($file)) {
            return null;
        }

        $data = file_get_contents($file);
        $cache = unserialize($data);

        if ($cache['expires'] < time()) {
            unlink($file);
            return null;
        }

        return $cache['value'];
    }

    public function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        $file = $this->getFilePath($key);
        $cache = [
            'value' => $value,
            'expires' => time() + $ttl
        ];

        return file_put_contents($file, serialize($cache)) !== false;
    }

    public function delete(string $key): bool
    {
        $file = $this->getFilePath($key);
        return !file_exists($file) || unlink($file);
    }

    public function clear(): bool
    {
        $files = glob($this->cacheDir . '/*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
        return true;
    }

    public function exists(string $key): bool
    {
        return $this->get($key) !== null;
    }

    private function getFilePath(string $key): string
    {
        return $this->cacheDir . '/' . md5($key) . '.cache';
    }
}

class CacheService
{
    private CacheAdapterInterface $adapter;
    private bool $enabled;
    private string $prefix;
    private array $stats;

    public function __construct(CacheAdapterInterface $adapter = null, bool $enabled = true, string $prefix = 'adminkit')
    {
        $this->adapter = $adapter ?: new FileCacheAdapter();
        $this->enabled = $enabled;
        $this->prefix = $prefix;
        $this->stats = ['hits' => 0, 'misses' => 0, 'sets' => 0];
    }

    /**
     * Get cached value
     */
    public function get(string $key, callable $callback = null, int $ttl = 3600): mixed
    {
        if (!$this->enabled) {
            return $callback ? $callback() : null;
        }

        $prefixedKey = $this->prefix . ':' . $key;
        $value = $this->adapter->get($prefixedKey);

        if ($value !== null) {
            $this->stats['hits']++;
            return $value;
        }

        $this->stats['misses']++;

        if ($callback) {
            $value = $callback();
            $this->set($key, $value, $ttl);
            return $value;
        }

        return null;
    }

    /**
     * Set cache value
     */
    public function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        if (!$this->enabled) {
            return true;
        }

        $this->stats['sets']++;
        $prefixedKey = $this->prefix . ':' . $key;
        return $this->adapter->set($prefixedKey, $value, $ttl);
    }

    /**
     * Delete cache entry
     */
    public function delete(string $key): bool
    {
        if (!$this->enabled) {
            return true;
        }

        $prefixedKey = $this->prefix . ':' . $key;
        return $this->adapter->delete($prefixedKey);
    }

    /**
     * Check if cache entry exists
     */
    public function exists(string $key): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $prefixedKey = $this->prefix . ':' . $key;
        return $this->adapter->exists($prefixedKey);
    }

    /**
     * Clear all cache
     */
    public function clear(): bool
    {
        if (!$this->enabled) {
            return true;
        }

        return $this->adapter->clear();
    }

    /**
     * Cache with tags for group invalidation
     */
    public function tags(array $tags): TaggedCache
    {
        return new TaggedCache($this, $tags);
    }

    /**
     * Remember pattern - get or set
     */
    public function remember(string $key, callable $callback, int $ttl = 3600): mixed
    {
        return $this->get($key, $callback, $ttl);
    }

    /**
     * Cache forever (1 year TTL)
     */
    public function forever(string $key, mixed $value): bool
    {
        return $this->set($key, $value, 365 * 24 * 3600);
    }

    /**
     * Cache query results
     */
    public function queryCache(string $sql, array $params, callable $callback, int $ttl = 600): mixed
    {
        $key = 'query:' . md5($sql . serialize($params));
        return $this->remember($key, $callback, $ttl);
    }

    /**
     * Cache user permissions
     */
    public function userPermissions(int $userId, callable $callback, int $ttl = 1800): array
    {
        $key = "user_permissions:{$userId}";
        return $this->remember($key, $callback, $ttl);
    }

    /**
     * Cache entity count
     */
    public function entityCount(string $entityName, callable $callback, int $ttl = 300): int
    {
        $key = "entity_count:{$entityName}";
        return $this->remember($key, $callback, $ttl);
    }

    /**
     * Cache menu structure
     */
    public function menuCache(int $userId, callable $callback, int $ttl = 1800): array
    {
        $key = "menu:{$userId}";
        return $this->remember($key, $callback, $ttl);
    }

    /**
     * Cache translations
     */
    public function translationCache(string $locale, callable $callback, int $ttl = 7200): array
    {
        $key = "translations:{$locale}";
        return $this->remember($key, $callback, $ttl);
    }

    /**
     * Invalidate entity cache
     */
    public function invalidateEntity(string $entityName, int $id = null): void
    {
        $this->delete("entity_count:{$entityName}");
        
        if ($id) {
            $this->delete("entity:{$entityName}:{$id}");
        }

        // Clear related caches
        $this->delete("dashboard_stats");
        $this->clearPattern("menu:*");
    }

    /**
     * Clear cache by pattern
     */
    public function clearPattern(string $pattern): void
    {
        // Simple implementation for file cache
        if ($this->adapter instanceof FileCacheAdapter) {
            $pattern = str_replace('*', '.*', $pattern);
            $pattern = "/^{$this->prefix}:{$pattern}$/";
            
            $cacheDir = sys_get_temp_dir() . '/adminkit_cache';
            $files = glob($cacheDir . '/*.cache');
            
            foreach ($files as $file) {
                $content = file_get_contents($file);
                $cache = unserialize($content);
                
                if (preg_match($pattern, $cache['key'] ?? '')) {
                    unlink($file);
                }
            }
        }
    }

    /**
     * Get cache statistics
     */
    public function getStats(): array
    {
        $hitRate = $this->stats['hits'] + $this->stats['misses'] > 0 
            ? round(($this->stats['hits'] / ($this->stats['hits'] + $this->stats['misses'])) * 100, 2)
            : 0;

        return [
            'hits' => $this->stats['hits'],
            'misses' => $this->stats['misses'],
            'sets' => $this->stats['sets'],
            'hit_rate' => $hitRate . '%',
            'enabled' => $this->enabled,
            'adapter' => get_class($this->adapter)
        ];
    }

    /**
     * Enable/disable cache
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    /**
     * Get cache adapter
     */
    public function getAdapter(): CacheAdapterInterface
    {
        return $this->adapter;
    }
}

class TaggedCache
{
    private CacheService $cache;
    private array $tags;

    public function __construct(CacheService $cache, array $tags)
    {
        $this->cache = $cache;
        $this->tags = $tags;
    }

    public function get(string $key): mixed
    {
        return $this->cache->get($this->taggedKey($key));
    }

    public function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        // Store tag mapping
        foreach ($this->tags as $tag) {
            $tagKey = "tag:{$tag}";
            $taggedKeys = $this->cache->get($tagKey, fn() => []);
            $taggedKeys[] = $this->taggedKey($key);
            $this->cache->set($tagKey, array_unique($taggedKeys), $ttl * 2);
        }

        return $this->cache->set($this->taggedKey($key), $value, $ttl);
    }

    public function flush(): void
    {
        foreach ($this->tags as $tag) {
            $tagKey = "tag:{$tag}";
            $taggedKeys = $this->cache->get($tagKey, fn() => []);
            
            foreach ($taggedKeys as $key) {
                $this->cache->delete($key);
            }
            
            $this->cache->delete($tagKey);
        }
    }

    private function taggedKey(string $key): string
    {
        return $key . ':tags:' . implode(',', $this->tags);
    }
}
