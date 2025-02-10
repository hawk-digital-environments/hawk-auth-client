<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Keycloak;


use Hawk\AuthClient\Cache\CacheAdapterInterface;
use Hawk\AuthClient\Keycloak\Value\ConnectionInfo;

/**
 * @internal This class is not part of the public API and may change at any time.
 */
class ConnectionInfoStorage
{
    private ConnectionInfo|null $connectionInfo = null;
    private CacheAdapterInterface $cache;
    private KeycloakApiClient $api;

    public function __construct(
        CacheAdapterInterface $cache,
        KeycloakApiClient     $api
    )
    {
        $this->cache = $cache;
        $this->api = $api;
    }

    /**
     * Returns generic information about this clients connection to the keycloak server.
     * @return ConnectionInfo
     */
    public function getConnectionInfo(): ConnectionInfo
    {
        return $this->connectionInfo ??= $this->cache->remember(
            'keycloak.client.connection_info',
            valueGenerator: fn() => $this->api->fetchConnectionInfo(),
            valueToCache: fn(ConnectionInfo $connectionInfo) => $connectionInfo->jsonSerialize(),
            cacheToValue: fn(array $data) => ConnectionInfo::fromArray($data),
        );
    }
}
