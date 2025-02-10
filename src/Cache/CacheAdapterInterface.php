<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Cache;


interface CacheAdapterInterface
{
    /**
     * Fetches a value from the cache.
     * @param string $key The unique key of this item in the cache.
     * @return mixed The value of the item from the cache, or null if not found.
     */
    public function get(string $key): mixed;

    /**
     * Persists data in the cache, uniquely referenced by a key with an optional expiration TTL time.
     * @param string $key The key of the item to store.
     * @param mixed $value The value of the item to store. Must be serializable.
     * @param int|null $ttl Optional. The TTL value of this item. Null should be interpreted as infinite(as long as possible) TTL.
     */
    public function set(string $key, mixed $value, int|null $ttl = null): void;

    /**
     * Deletes an item from the cache by its unique key.
     * @param string $key The key of the item to delete.
     */
    public function delete(string $key): void;

    /**
     * Helper to store and retrieve values in the cache.
     * @param string $key A unique key for the value to store
     * @param callable $valueGenerator A callable that generates the value if it is not found in the cache. If the value is found in the cache this callable will not be called.
     *                                 The result of this function will NOT pass through the cacheToValue callable.
     * @param callable|null $valueToCache A callable that transforms the value to be stored in the cache. If omitted the value will be stored as is.
     * @param callable|null $cacheToValue A callable that transforms the value from the cache. If omitted the value will be returned as is.
     * @param int|null|callable(mixed):int $ttl The ttl in seconds, or null if the item should be cached indefinitely. Maybe a callable that takes the value as an argument and returns the ttl.
     * @return mixed The value stored in the cache
     */
    public function remember(
        string            $key,
        callable          $valueGenerator,
        callable|null     $valueToCache = null,
        callable|null     $cacheToValue = null,
        int|null|callable $ttl = null
    ): mixed;
}
