<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Cache\Util;


use Hawk\AuthClient\Cache\CacheAdapterInterface;

abstract class AbstractKeyExtendingCache implements CacheAdapterInterface
{
    protected CacheAdapterInterface $concreteCache;

    public function __construct(CacheAdapterInterface $concreteCache)
    {
        $this->concreteCache = $concreteCache;
    }

    /**
     * MUST return the key with the extension of the namespace.
     *
     * @param string $key
     * @return string
     */
    abstract protected function extendKey(string $key): string;

    /**
     * @inheritDoc
     */
    #[\Override] public function get(string $key): mixed
    {
        return $this->concreteCache->get($this->extendKey($key));
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function set(string $key, mixed $value, int|null $ttl = null): void
    {
        $this->concreteCache->set($this->extendKey($key), $value, $ttl);
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function delete(string $key): void
    {
        $this->concreteCache->delete($this->extendKey($key));
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function remember(
        string            $key,
        callable          $valueGenerator,
        ?callable         $valueToCache = null,
        ?callable         $cacheToValue = null,
        callable|int|null $ttl = null
    ): mixed
    {
        return $this->concreteCache->remember(
            $this->extendKey($key),
            $valueGenerator,
            $valueToCache,
            $cacheToValue,
            $ttl
        );
    }
}
