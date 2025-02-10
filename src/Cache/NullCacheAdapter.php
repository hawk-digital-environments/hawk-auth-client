<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Cache;


use Hawk\AuthClient\Cache\Util\RememberingCacheTrait;

class NullCacheAdapter implements CacheAdapterInterface
{
    use RememberingCacheTrait;

    /**
     * @inheritDoc
     */
    #[\Override] public function get(string $key): mixed
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function set(string $key, mixed $value, int|null $ttl = null): void
    {
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function delete(string $key): void
    {
    }
}
