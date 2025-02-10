<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Util;


use Hawk\AuthClient\Container;

/**
 * @internal This class is not part of the public API and may change at any time.
 */
class LocalCacheFlusher
{
    protected Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * This is currently the absolute brute force method to flush all caches.
     * It will flush all caches that are currently known to the container.
     * We may want to add more fine-grained control in the future.
     * @return void
     */
    public function flushCache(): void
    {
        $this->container->getCacheBusterStorage()->flush();
        $this->container->getResourceCache()->flushResolved();
        $this->container->getUserCache()->flushResolved();
        $this->container->getRoleStorage()->flushResolved();
        $this->container->getGroupStorage()->flushResolved();
        $this->container->getProfileStorage()->flushResolved();
        $this->container->getPermissionStorage()->flushResolved();
    }
}
