<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Users;


use Hawk\AuthClient\Cache\CacheAdapterInterface;
use Hawk\AuthClient\Cache\Util\AbstractEntityCache;
use Hawk\AuthClient\Groups\Value\Group;
use Hawk\AuthClient\Keycloak\ConnectionInfoStorage;
use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Resources\Value\Resource;
use Hawk\AuthClient\Resources\Value\ResourceScopes;
use Hawk\AuthClient\Roles\Value\Role;
use Hawk\AuthClient\Users\Value\User;
use Hawk\AuthClient\Users\Value\UserConstraints;
use Hawk\AuthClient\Util\Uuid;
use League\OAuth2\Client\Token\AccessTokenInterface;

/**
 * @extends AbstractEntityCache<User>
 * @internal This class is not part of the public API and may change at any time.
 */
class UserCache extends AbstractEntityCache
{
    protected UserFactory $userFactory;
    protected KeycloakApiClient $api;
    protected ConnectionInfoStorage $connectionInfoStorage;
    protected Uuid|null $clientUuid = null;
    protected Uuid|null $clientServiceUserUuid = null;

    public function __construct(
        CacheAdapterInterface $cache,
        UserFactory           $userFactory,
        KeycloakApiClient     $api,
        ConnectionInfoStorage $connectionInfoStorage
    )
    {
        parent::__construct($cache);
        $this->userFactory = $userFactory;
        $this->api = $api;
        $this->connectionInfoStorage = $connectionInfoStorage;
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function getAllByIds(Uuid ...$ids): iterable
    {
        return parent::getAllByIds(...$this->convertClientUUidToServiceUserUuidInList(...$ids));
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function getOne(Uuid $id, bool $fetchMissing = true): object|null
    {
        return parent::getOne($this->convertClientUuidToServiceUserUuid($id), $fetchMissing);
    }

    protected function convertClientUuidToServiceUserUuid(Uuid $id): Uuid
    {
        $this->initializeClientUuidMap();
        return (string)$id === (string)$this->clientUuid ? $this->clientServiceUserUuid : $id;
    }

    protected function convertClientUUidToServiceUserUuidInList(Uuid ...$ids): array
    {
        $this->initializeClientUuidMap();
        return Uuid::fromList(
            ...array_map(
                fn(Uuid $id) => (string)$id === (string)$this->clientUuid
                    ? $this->clientServiceUserUuid
                    : $id,
                $ids
            )
        );
    }

    protected function initializeClientUuidMap(): void
    {
        if ($this->clientUuid === null || $this->clientServiceUserUuid === null) {
            $connectionInfo = $this->connectionInfoStorage->getConnectionInfo();
            $this->clientUuid = $connectionInfo->getClientUuid();
            $this->clientServiceUserUuid = $connectionInfo->getClientServiceAccountUuid();
        }
    }

    /**
     * Uses an additional hop in the cache pool to resolve the user id by the token.
     *
     * @param AccessTokenInterface $token
     * @param callable():(User|null) $fallback
     * @return User|null
     */
    public function getOneByToken(AccessTokenInterface $token, callable $fallback): User|null
    {
        $userId = $this->cache->remember(
            'keycloak.user.by_token.' . hash('sha256', $token->getToken()),
            valueGenerator: fn() => $fallback()?->getId(),
            ttl: 60 * 15
        );

        if ($userId === null) {
            return null;
        }

        return $this->getOne($userId);
    }

    /**
     * Returns a lazy stream of user ids that match the given constraints.
     * @param UserConstraints|null $constraints
     * @return iterable<Uuid>
     */
    public function getUserIdStream(UserConstraints|null $constraints): iterable
    {
        yield from $this->api->fetchUserIdStream($constraints, $this->cache);
    }

    /**
     * Returns a lazy stream of user ids that are members of the given group.
     * @param Group $group
     * @return iterable<Uuid>
     */
    public function getGroupMemberIdStream(Group $group): iterable
    {
        yield from $this->api->fetchGroupMemberIdStream($group->getId(), $this->cache);
    }

    /**
     * Returns a lazy stream of user ids that are members of the given role.
     * @param Role $role
     * @return iterable<Uuid>
     */
    public function getRoleMemberIdStream(Role $role): iterable
    {
        return $this->api->fetchRoleMemberIdStream($role->getId(), $this->cache);
    }

    /**
     * Returns a lazy stream of user ids that have access to the given resource.
     * @param Resource $resource
     * @param bool $includeOwner
     * @return iterable<array{Uuid,ResourceScopes}>
     */
    public function getResourceUserIdStream(Resource $resource, bool $includeOwner): iterable
    {
        if ($includeOwner) {
            yield [$resource->getOwner()->getId(), $resource->getScopes()];
        }

        yield from $this->api->fetchResourceUserIdStream($resource, $this->cache);
    }

    /**
     * @inheritDoc
     */
    #[\Override] protected function getCacheKey(Uuid $id): string
    {
        return 'keycloak.user.by_id.' . $id;
    }

    /**
     * @inheritDoc
     */
    #[\Override] protected function fetchItems(Uuid ...$ids): iterable
    {
        return $this->api->fetchUsersByIds(...$ids);
    }

    /**
     * @inheritDoc
     */
    #[\Override] protected function unserializeObject(Uuid $id, array $data): object
    {
        return $this->userFactory->makeUserFromCacheData($data);
    }

    /**
     * @inheritDoc
     */
    #[\Override] protected function serializeObject(Uuid $id, object $item): array
    {
        return $item->jsonSerialize();
    }
}
