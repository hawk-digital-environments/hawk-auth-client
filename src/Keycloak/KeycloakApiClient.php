<?php
/** @noinspection PhpPropertyOnlyWrittenInspection */
declare(strict_types=1);


namespace Hawk\AuthClient\Keycloak;


use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use Hawk\AuthClient\Cache\CacheAdapterInterface;
use Hawk\AuthClient\Groups\Value\GroupList;
use Hawk\AuthClient\Keycloak\Query\FetchApiTokenQuery;
use Hawk\AuthClient\Keycloak\Query\FetchCacheBusterQuery;
use Hawk\AuthClient\Keycloak\Query\FetchConnectionInfoQuery;
use Hawk\AuthClient\Keycloak\Query\FetchGroupMemberIdStreamQuery;
use Hawk\AuthClient\Keycloak\Query\FetchGroupsQuery;
use Hawk\AuthClient\Keycloak\Query\FetchProfileDataQuery;
use Hawk\AuthClient\Keycloak\Query\FetchProfileStructureQuery;
use Hawk\AuthClient\Keycloak\Query\FetchResourceByNameQuery;
use Hawk\AuthClient\Keycloak\Query\FetchResourceIdStreamQuery;
use Hawk\AuthClient\Keycloak\Query\FetchResourcesByIdsQuery;
use Hawk\AuthClient\Keycloak\Query\FetchResourceScopesGrantedToUserQuery;
use Hawk\AuthClient\Keycloak\Query\FetchResourceUserIdStreamQuery;
use Hawk\AuthClient\Keycloak\Query\FetchRoleMembersIdStreamQuery;
use Hawk\AuthClient\Keycloak\Query\FetchRolesQuery;
use Hawk\AuthClient\Keycloak\Query\FetchUserIdStreamQuery;
use Hawk\AuthClient\Keycloak\Query\FetchUsersByIdsQuery;
use Hawk\AuthClient\Keycloak\Query\RemoveResourceQuery;
use Hawk\AuthClient\Keycloak\Query\SetResourceUserPermissionsQuery;
use Hawk\AuthClient\Keycloak\Query\UpdateProfileDataQuery;
use Hawk\AuthClient\Keycloak\Query\UpdateProfileStructureQuery;
use Hawk\AuthClient\Keycloak\Query\UpsertResourceQuery;
use Hawk\AuthClient\Keycloak\Value\ApiToken;
use Hawk\AuthClient\Keycloak\Value\CacheBuster;
use Hawk\AuthClient\Keycloak\Value\ConnectionConfig;
use Hawk\AuthClient\Keycloak\Value\ConnectionInfo;
use Hawk\AuthClient\Profiles\Structure\Util\ProfileStructureData;
use Hawk\AuthClient\Profiles\Value\UserProfile;
use Hawk\AuthClient\Resources\ResourceFactory;
use Hawk\AuthClient\Resources\Value\Resource;
use Hawk\AuthClient\Resources\Value\ResourceBuilder;
use Hawk\AuthClient\Resources\Value\ResourceConstraints;
use Hawk\AuthClient\Resources\Value\ResourceScopes;
use Hawk\AuthClient\Roles\Value\RoleList;
use Hawk\AuthClient\Users\UserFactory;
use Hawk\AuthClient\Users\Value\User;
use Hawk\AuthClient\Users\Value\UserConstraints;
use Hawk\AuthClient\Util\LocalCacheFlusher;
use Psr\Clock\ClockInterface;

/**
 * @internal This class is not part of the public API and may change at any time.
 */
class KeycloakApiClient
{
    protected ConnectionConfig $config;
    protected ClientInterface $httpClient;
    protected ClockInterface $clock;
    protected ApiTokenStorage $tokenStorage;
    protected ConnectionInfoStorage $connectionInfoStorage;
    protected UserFactory $userFactory;
    protected ResourceFactory $resourceFactory;
    protected bool $isRequestingToken = false;
    protected LocalCacheFlusher $cacheFlusher;

    public function __construct(
        ConnectionConfig  $config,
        ClientInterface   $httpClient,
        ClockInterface    $clock,
        UserFactory       $userFactory,
        ResourceFactory   $resourceFactory,
        LocalCacheFlusher $cacheFlusher
    )
    {
        $this->config = $config;
        $this->clock = $clock;
        $this->httpClient = $httpClient;
        $this->userFactory = $userFactory;
        $this->resourceFactory = $resourceFactory;
        $this->cacheFlusher = $cacheFlusher;
    }

    public function setTokenStorage(ApiTokenStorage $storage): void
    {
        $this->tokenStorage = $storage;
    }

    public function setConnectionInfoStorage(ConnectionInfoStorage $connectionInfoStorage): void
    {
        $this->connectionInfoStorage = $connectionInfoStorage;
    }

    public function fetchApiToken(): ApiToken
    {
        try {
            $this->isRequestingToken = true;
            return (new FetchApiTokenQuery($this->config, $this->clock))
                ->execute($this->getConfiguredClient());
        } finally {
            $this->isRequestingToken = false;
        }
    }

    public function fetchConnectionInfo(): ConnectionInfo
    {
        return (new FetchConnectionInfoQuery())
            ->execute($this->getConfiguredClient());
    }

    public function fetchCacheBuster(): CacheBuster
    {
        return (new FetchCacheBusterQuery($this->clock))
            ->execute($this->getConfiguredClient());
    }

    public function fetchGrantedResourceScopesForUser(Resource $resource, User $user): ResourceScopes|null
    {
        return (new FetchResourceScopesGrantedToUserQuery($resource, $user))
            ->execute($this->getConfiguredClient());
    }

    public function fetchGroups(): GroupList
    {
        return (new FetchGroupsQuery())
            ->execute($this->getConfiguredClient());
    }

    public function fetchRoles(): RoleList
    {
        return (new FetchRolesQuery())
            ->execute($this->getConfiguredClient());
    }

    public function fetchUserIdStream(UserConstraints|null $constraints, CacheAdapterInterface $cache): iterable
    {
        return (new FetchUserIdStreamQuery($constraints))
            ->execute($this->getConfiguredClient(), $cache);
    }

    public function fetchUsersByIds(string ...$userIds): iterable
    {
        return (new FetchUsersByIdsQuery(
            $this->userFactory,
            ...$userIds
        ))->execute($this->getConfiguredClient());
    }

    public function fetchGroupMemberIdStream(string $groupId, CacheAdapterInterface $cache): iterable
    {
        return (new FetchGroupMemberIdStreamQuery($groupId))
            ->execute($this->getConfiguredClient(), $cache);
    }

    public function fetchRoleMemberIdStream(string $roleId, CacheAdapterInterface $cache): iterable
    {
        return (new FetchRoleMembersIdStreamQuery($roleId))
            ->execute($this->getConfiguredClient(), $cache);
    }

    public function fetchProfileStructure(): ProfileStructureData
    {
        return (new FetchProfileStructureQuery())
            ->execute($this->getConfiguredClient());
    }

    public function updateProfileStructure(ProfileStructureData $profileStructure): void
    {
        (new UpdateProfileStructureQuery($profileStructure))
            ->execute($this->getConfiguredClient());
        $this->cacheFlusher->flushCache();
    }

    public function fetchUserProfile(User $user): UserProfile
    {
        return (new FetchProfileDataQuery($this->config, $user))
            ->execute($this->getConfiguredClient());
    }

    public function updateUserProfile(User $user, array $changeSet): void
    {
        (new UpdateProfileDataQuery($user, $this->fetchUserProfile($user), $changeSet))
            ->execute($this->getConfiguredClient());
        $this->cacheFlusher->flushCache();
    }

    public function fetchResourceByName(string $resourceName): Resource|null
    {
        return (new FetchResourceByNameQuery($this->resourceFactory, $resourceName))
            ->execute($this->getConfiguredClient());
    }

    public function fetchResourcesByIds(string ...$resourceIds): iterable
    {
        return (new FetchResourcesByIdsQuery(
            $this->resourceFactory,
            ...$resourceIds
        ))->execute($this->getConfiguredClient());
    }

    public function fetchResourceIdStream(ResourceConstraints|null $constraints, CacheAdapterInterface $cache): iterable
    {
        return (new FetchResourceIdStreamQuery($constraints))
            ->execute($this->getConfiguredClient(), $cache);
    }

    public function fetchResourceUserIdStream(Resource $resource, CacheAdapterInterface $cache): iterable
    {
        return (new FetchResourceUserIdStreamQuery($resource))
            ->execute($this->getConfiguredClient(), $cache);
    }

    public function upsertResource(ResourceBuilder $builder): void
    {
        (new UpsertResourceQuery($builder))
            ->execute($this->getConfiguredClient());
        $this->cacheFlusher->flushCache();
    }

    public function removeResource(Resource $resource): void
    {
        (new RemoveResourceQuery($resource))
            ->execute($this->getConfiguredClient());
        $this->cacheFlusher->flushCache();
    }

    public function setResourceUserPermissions(Resource $resource, User $user, array|null $scopes): void
    {
        (new SetResourceUserPermissionsQuery($resource, $user, $scopes))
            ->execute($this->getConfiguredClient());
        $this->cacheFlusher->flushCache();
    }

    /**
     * Creates a wrapper around the HTTP client that automatically adds the Authorization header
     * and replaces {realm} and {clientId} in the URL.
     * @return ClientInterface
     */
    protected function getConfiguredClient(): ClientInterface
    {
        $stack = HandlerStack::create(function ($request, $options) {
            unset($options['handler']);
            return $this->httpClient->sendAsync($request, $options);
        });

        // Only add the Authorization header if we are not requesting a token (to avoid infinite loops)
        if (!$this->isRequestingToken) {
            $stack->push(function (callable $next) {
                return function (Request $request, array $options) use ($next) {
                    if ($request->hasHeader('Authorization')) {
                        return $next($request, $options);
                    }

                    return $next(
                        $request->withHeader('Authorization', 'Bearer ' . $this->tokenStorage->getToken()),
                        $options
                    );
                };
            });
        }

        // Automatically replace {realm} and {clientId} in the URL
        $stack->push(function (callable $next) {
            return function (Request $request, array $options) use ($next) {
                return $next(
                    $request->withUri(
                        $request->getUri()->withPath(
                            preg_replace_callback(
                                '/\{realm}|\{clientId}|\{clientUuid}/',
                                function ($matches) {
                                    return match ($matches[0]) {
                                        '{realm}' => $this->config->getRealm(),
                                        '{clientId}' => $this->config->getClientId(),
                                        '{clientUuid}' => $this->connectionInfoStorage->getConnectionInfo()->getClientUuid()
                                    };
                                },
                                urldecode($request->getUri()->getPath())
                            )
                        )
                    ),
                    $options
                );
            };
        });

        return new Client([
            'handler' => $stack,
            'base_uri' => $this->config->getInternalKeycloakUrl() . '/'
        ]);
    }
}
