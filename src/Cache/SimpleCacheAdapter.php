<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Cache;


use Hawk\AuthClient\Cache\Util\RememberingCacheTrait;
use Psr\SimpleCache\CacheInterface;

class SimpleCacheAdapter implements CacheAdapterInterface
{
    use RememberingCacheTrait;

    private CacheInterface $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function get(string $key): mixed
    {
        return $this->cache->get($key);
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function set(string $key, mixed $value, int|null $ttl = null): void
    {
        $this->cache->set($key, $value, $ttl);
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function delete(string $key): void
    {
        $this->cache->delete($key);
    }
}
