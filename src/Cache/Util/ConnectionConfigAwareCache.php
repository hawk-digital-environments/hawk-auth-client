<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Cache\Util;


use Hawk\AuthClient\Cache\CacheAdapterInterface;
use Hawk\AuthClient\Keycloak\Value\ConnectionConfig;

class ConnectionConfigAwareCache extends AbstractKeyExtendingCache
{
    private string $configHash;

    public function __construct(
        CacheAdapterInterface $concreteCache,
        ConnectionConfig      $config
    )
    {
        parent::__construct($concreteCache);
        $this->configHash = $config->getHash();
    }

    /**
     * @inheritDoc
     */
    #[\Override] protected function extendKey(string $key): string
    {
        return $key . '-' . $this->configHash;
    }
}
