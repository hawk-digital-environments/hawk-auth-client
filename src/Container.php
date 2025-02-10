<?php
declare(strict_types=1);


namespace Hawk\AuthClient;


use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Hawk\AuthClient\Auth\KeycloakProvider;
use Hawk\AuthClient\Auth\StatefulAuth;
use Hawk\AuthClient\Auth\StatefulUserTokenStorage;
use Hawk\AuthClient\Auth\StatelessAuth;
use Hawk\AuthClient\Cache\CacheAdapterInterface;
use Hawk\AuthClient\Cache\FileCacheAdapter;
use Hawk\AuthClient\Cache\Util\CacheBusterAwareCache;
use Hawk\AuthClient\Cache\Util\ConnectionConfigAwareCache;
use Hawk\AuthClient\Clock\SystemClock;
use Hawk\AuthClient\Groups\GroupStorage;
use Hawk\AuthClient\Keycloak\ApiTokenStorage;
use Hawk\AuthClient\Keycloak\CacheBusterStorage;
use Hawk\AuthClient\Keycloak\ConnectionInfoStorage;
use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Keycloak\Value\ConnectionConfig;
use Hawk\AuthClient\Permissions\GuardFactory;
use Hawk\AuthClient\Permissions\PermissionStorage;
use Hawk\AuthClient\Profiles\ProfileLayer;
use Hawk\AuthClient\Profiles\ProfileStorage;
use Hawk\AuthClient\Request\PhpRequestAdapter;
use Hawk\AuthClient\Request\RequestAdapterInterface;
use Hawk\AuthClient\Resources\ResourceCache;
use Hawk\AuthClient\Resources\ResourceFactory;
use Hawk\AuthClient\Resources\ResourceStorage;
use Hawk\AuthClient\Roles\RoleStorage;
use Hawk\AuthClient\Session\PhpSessionAdapter;
use Hawk\AuthClient\Session\SessionAdapterInterface;
use Hawk\AuthClient\Users\UserCache;
use Hawk\AuthClient\Users\UserFactory;
use Hawk\AuthClient\Users\UserStorage;
use Hawk\AuthClient\Users\Value\UserContext;
use Hawk\AuthClient\Util\LocalCacheFlusher;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @codeCoverageIgnore
 * @internal This class is not part of the public API and may change at any time.
 */
class Container
{
    protected array $singletons = [];

    protected ConnectionConfig $config;
    protected array $providerOptions;
    protected array $providerCollaborators;

    public function __construct(
        ConnectionConfig $config,
        array            $providerOptions,
        array            $providerCollaborators
    )
    {
        $this->config = $config;
        $this->providerOptions = $providerOptions;
        $this->providerCollaborators = $providerCollaborators;
    }

    protected function getSession(): SessionAdapterInterface
    {
        return $this->makeSingleton(SessionAdapterInterface::class,
            static fn() => new PhpSessionAdapter());
    }

    public function setSession(SessionAdapterInterface|null $session): self
    {
        return $this->setSingleton(SessionAdapterInterface::class, $session);
    }

    protected function getHttpClient(): ClientInterface
    {
        return $this->makeSingleton(ClientInterface::class,
            static fn() => new Client());
    }

    public function setHttpClient(ClientInterface|null $client): self
    {
        return $this->setSingleton(ClientInterface::class, $client);
    }

    protected function getRequest(): RequestAdapterInterface
    {
        return $this->makeSingleton(RequestAdapterInterface::class,
            static fn() => new PhpRequestAdapter());
    }

    public function setRequest(RequestAdapterInterface|null $request): self
    {
        return $this->setSingleton(RequestAdapterInterface::class, $request);
    }

    protected function getCache(): CacheAdapterInterface
    {
        return $this->makeSingleton(CacheAdapterInterface::class,
            fn() => new FileCacheAdapter(clock: $this->getClock()));
    }

    protected function getConfigAwareCache(): CacheAdapterInterface
    {
        return $this->makeSingleton(CacheAdapterInterface::class,
            fn() => new ConnectionConfigAwareCache($this->getCache(), $this->config));
    }

    public function setCache(CacheAdapterInterface|null $cache): self
    {
        return $this->setSingleton(CacheAdapterInterface::class, $cache);
    }

    protected function getCacheBusterAwareCache(): CacheAdapterInterface
    {
        return $this->makeSingleton(CacheBusterAwareCache::class,
            fn() => new CacheBusterAwareCache($this->getCache(), $this->getCacheBusterStorage())
        );
    }

    protected function getLogger(): LoggerInterface
    {
        return $this->makeSingleton(LoggerInterface::class,
            fn() => new NullLogger());
    }

    public function setLogger(LoggerInterface|null $logger): self
    {
        return $this->setSingleton(LoggerInterface::class, $logger);
    }

    protected function getClock(): ClockInterface
    {
        return $this->makeSingleton(ClockInterface::class, static fn() => new SystemClock());
    }

    public function setClock(ClockInterface|null $clock): self
    {
        return $this->setSingleton(ClockInterface::class, $clock);
    }

    protected function getApiTokenStorage(): ApiTokenStorage
    {
        return $this->makeSingleton(ApiTokenStorage::class,
            fn() => new ApiTokenStorage(
                $this->getConfigAwareCache(),
                $this->getClock(),
                $this->getKeycloakApiClient()
            )
        );
    }

    public function getCacheBusterStorage(): CacheBusterStorage
    {
        return $this->makeSingleton(CacheBusterStorage::class,
            fn() => new CacheBusterStorage(
                $this->getConfigAwareCache(),
                $this->getKeycloakApiClient()
            )
        );
    }

    protected function getConnectionInfoStorage(): ConnectionInfoStorage
    {
        return $this->makeSingleton(ConnectionInfoStorage::class,
            fn() => new ConnectionInfoStorage(
                $this->getConfigAwareCache(),
                $this->getKeycloakApiClient()
            )
        );
    }

    public function getPermissionStorage(): PermissionStorage
    {
        return $this->makeSingleton(PermissionStorage::class,
            fn() => new PermissionStorage(
                $this->getCacheBusterAwareCache(),
                $this->getKeycloakApiClient(),
                $this->getResourceStorage()
            )
        );
    }

    public function getGroupStorage(): GroupStorage
    {
        return $this->makeSingleton(GroupStorage::class,
            fn() => new GroupStorage(
                $this->getCacheBusterAwareCache(),
                $this->getKeycloakApiClient()
            )
        );
    }

    public function getRoleStorage(): RoleStorage
    {
        return $this->makeSingleton(RoleStorage::class,
            fn() => new RoleStorage(
                $this->getCacheBusterAwareCache(),
                $this->getKeycloakApiClient()
            )
        );
    }

    public function getUserStorage(): UserStorage
    {
        return $this->makeSingleton(UserStorage::class,
            fn() => new UserStorage(
                $this->getKeycloakApiClient(),
                $this->getUserCache()
            )
        );
    }

    public function getUserCache(): UserCache
    {
        return $this->makeSingleton(UserCache::class,
            fn() => new UserCache(
                $this->getCacheBusterAwareCache(),
                $this->getUserFactory(),
                $this->getKeycloakApiClient()
            )
        );
    }

    protected function getUserFactory(): UserFactory
    {
        return $this->makeSingleton(UserFactory::class,
            fn() => new UserFactory(
                $this->config,
                $this->getUserContext()
            )
        );
    }

    protected function getUserContext(): UserContext
    {
        return $this->makeSingleton(UserContext::class,
            fn() => new UserContext($this)
        );
    }

    public function getGuardFactory(): GuardFactory
    {
        return $this->makeSingleton(GuardFactory::class,
            fn() => new GuardFactory(
                $this->getPermissionStorage(),
                $this->getUserStorage()
            )
        );
    }

    protected function getKeycloakApiClient(): KeycloakApiClient
    {
        return $this->makeSingleton(KeycloakApiClient::class,
            function () {
                $client = new KeycloakApiClient(
                    $this->config,
                    $this->getHttpClient(),
                    $this->getClock(),
                    $this->getUserFactory(),
                    $this->getResourceFactory(),
                    new LocalCacheFlusher($this)
                );

                // We MUST set the client as a singleton here to avoid circular dependencies
                $this->setSingleton(KeycloakApiClient::class, $client);

                $client->setTokenStorage($this->getApiTokenStorage());
                $client->setConnectionInfoStorage($this->getConnectionInfoStorage());

                return $client;
            }
        );
    }

    public function getKeycloakOauthProvider(): KeycloakProvider
    {
        return $this->makeSingleton(KeycloakProvider::class,
            fn() => new KeycloakProvider(
                array_merge(
                    [
                        'realm' => $this->config->getRealm(),
                        'clientId' => $this->config->getClientId(),
                        'clientSecret' => $this->config->getClientSecret(),
                        'redirectUri' => $this->config->getRedirectUrl(),
                        'redirectUriAfterLogout' => $this->config->getRedirectUrlAfterLogout(),
                        'publicKeycloakUrl' => $this->config->getPublicKeycloakUrl(),
                        'internalKeycloakUrl' => $this->config->getInternalKeycloakUrl(),
                    ],
                    $this->providerOptions ?? []
                ),
                array_merge(
                    [
                        'httpClient' => $this->getHttpClient(),
                        'userFactory' => $this->getUserFactory()
                    ],
                    $this->providerCollaborators
                )
            )
        );
    }

    public function getStatefulAuth(): StatefulAuth
    {
        return $this->makeSingleton(StatefulAuth::class,
            fn() => new StatefulAuth(
                $this->getRequest(),
                new StatefulUserTokenStorage(
                    $this->getSession(),
                    $this->getCacheBusterStorage()
                ),
                $this->getSession(),
                $this->getKeycloakOauthProvider(),
                $this->getUserStorage(),
                $this->getGuardFactory(),
                $this->getLogger()
            )
        );
    }

    public function getStatelessAuth(): StatelessAuth
    {
        return $this->makeSingleton(StatelessAuth::class,
            fn() => new StatelessAuth(
                $this->getUserStorage(),
                $this->getGuardFactory(),
                $this->getKeycloakOauthProvider(),
                $this->getLogger()
            ));
    }

    public function getProfileLayer(): ProfileLayer
    {
        return $this->makeSingleton(ProfileLayer::class,
            fn() => new ProfileLayer(
                $this->config,
                $this->getKeycloakApiClient(),
                $this->getProfileStorage()
            )
        );
    }

    public function getProfileStorage(): ProfileStorage
    {
        return $this->makeSingleton(ProfileStorage::class,
            fn() => new ProfileStorage(
                $this->config,
                $this->getCacheBusterAwareCache(),
                $this->getKeycloakApiClient()
            )
        );
    }

    public function getResourceStorage(): ResourceStorage
    {
        return $this->makeSingleton(ResourceStorage::class,
            fn() => new ResourceStorage(
                $this->getResourceCache(),
                $this->getKeycloakApiClient(),
                $this->getUserStorage()
            )
        );
    }

    public function getResourceCache(): ResourceCache
    {
        return $this->makeSingleton(ResourceCache::class,
            fn() => new ResourceCache(
                $this->getCacheBusterAwareCache(),
                $this->getResourceFactory(),
                $this->getKeycloakApiClient()
            )
        );
    }

    protected function getResourceFactory(): ResourceFactory
    {
        return $this->makeSingleton(ResourceFactory::class,
            fn() => new ResourceFactory($this->getUserContext())
        );
    }

    /**
     * @template T
     * @param class-string<T> $class
     * @param callable $factory
     * @return object|T
     */
    protected function makeSingleton(string $class, callable $factory): object
    {
        if (!isset($this->singletons[$class])) {
            $result = $factory();
            // If service got resolved while the factory was executed, do not override the singleton
            if (!isset($this->singletons[$class])) {
                $this->singletons[$class] = $result;
            }
        }

        return $this->singletons[$class];
    }

    protected function setSingleton(string $class, object|null $instance): self
    {
        if (!$instance) {
            return $this;
        }

        $this->singletons[$class] = $instance;

        return $this;
    }
}
