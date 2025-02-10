<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Keycloak;


use Hawk\AuthClient\Cache\CacheAdapterInterface;
use Hawk\AuthClient\Keycloak\Value\CacheBuster;

/**
 * @internal This class is not part of the public API and may change at any time.
 */
class CacheBusterStorage
{
    protected CacheAdapterInterface $cache;
    protected CacheBuster|null $cacheBuster = null;
    protected KeycloakApiClient $api;

    public function __construct(CacheAdapterInterface $cache, KeycloakApiClient $api)
    {
        $this->cache = $cache;
        $this->api = $api;
    }

    public function getCacheBuster(): CacheBuster
    {
        return $this->cacheBuster ??= $this->cache->remember(
            'keycloak.client.cacheBreaker',
            valueGenerator: fn() => $this->api->fetchCacheBuster(),
            valueToCache: fn(CacheBuster $cacheBuster) => (string)$cacheBuster,
            cacheToValue: fn(string $cacheBuster) => new CacheBuster($cacheBuster),
            ttl: 10
        );
    }

    public function flush(): void
    {
        $this->cacheBuster = null;
        $this->cache->delete('keycloak.client.cacheBreaker');
    }
}
