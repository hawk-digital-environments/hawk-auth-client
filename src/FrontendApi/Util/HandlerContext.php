<?php
declare(strict_types=1);


namespace Hawk\AuthClient\FrontendApi\Util;

use Hawk\AuthClient\Auth\KeycloakProvider;
use Hawk\AuthClient\Container;
use Hawk\AuthClient\Keycloak\Value\CacheBuster;
use Hawk\AuthClient\Permissions\PermissionStorage;
use Hawk\AuthClient\Users\UserStorage;

/**
 * @internal This class is not part of the public API and may change at any time.
 */
class HandlerContext
{
    protected Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function getKeycloakProvider(): KeycloakProvider
    {
        return $this->container->getKeycloakOauthProvider();
    }

    public function getUserStorage(): UserStorage
    {
        return $this->container->getUserStorage();
    }

    public function getCacheBuster(): CacheBuster
    {
        return $this->container->getCacheBusterStorage()->getCacheBuster();
    }

    public function getPermissionStorage(): PermissionStorage
    {
        return $this->container->getPermissionStorage();
    }

    public function getCurrentUserFinder(): CurrentUserFinder
    {
        return new CurrentUserFinder(
            $this->getUserStorage(),
            $this->getKeycloakProvider()
        );
    }
}
