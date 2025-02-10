<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Cache\Util;


trait RememberingCacheTrait
{
    /**
     * @see CacheAdapterInterface::get()
     */
    abstract public function get(string $key): mixed;

    /**
     * @see CacheAdapterInterface::set()
     */
    abstract public function set(string $key, mixed $value, int|null $ttl = null): void;

    /**
     * @see CacheAdapterInterface::remember()
     */
    public function remember(
        string            $key,
        callable          $valueGenerator,
        callable|null     $valueToCache = null,
        callable|null     $cacheToValue = null,
        int|null|callable $ttl = null
    ): mixed
    {
        $value = $this->get($key);

        if ($value === null) {
            $value = $valueGenerator();
            $this->set(
                $key,
                $valueToCache ? $valueToCache($value) : $value,
                is_callable($ttl) ? $ttl($value) : $ttl
            );
            return $value;
        }

        return $cacheToValue ? $cacheToValue($value) : $value;
    }
}
