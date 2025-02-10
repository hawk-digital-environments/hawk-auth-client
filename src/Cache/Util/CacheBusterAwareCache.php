<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Cache\Util;


use Hawk\AuthClient\Cache\CacheAdapterInterface;
use Hawk\AuthClient\Keycloak\CacheBusterStorage;
use Hawk\AuthClient\Keycloak\Value\CacheBuster;

/**
 * Special variant of the cache adapter that is aware of a {@see CacheBuster}.
 * Every time the cache buster changes, the cache is effectively invalidated.
 * @internal This class is not part of the public API and may change at any time.
 */
class CacheBusterAwareCache extends AbstractKeyExtendingCache
{
    private CacheBusterStorage $busterStorage;

    public function __construct(
        CacheAdapterInterface $concreteCache,
        CacheBusterStorage    $busterStorage
    )
    {
        parent::__construct($concreteCache);
        $this->busterStorage = $busterStorage;
    }
    
    /**
     * @inheritDoc
     */
    #[\Override] protected function extendKey(string $key): string
    {
        return $key . '-' . $this->busterStorage->getCacheBuster();
    }
}
