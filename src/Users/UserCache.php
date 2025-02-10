<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Users;


use Hawk\AuthClient\Cache\CacheAdapterInterface;
use Hawk\AuthClient\Cache\Util\AbstractEntityCache;
use Hawk\AuthClient\Groups\Value\Group;
use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Resources\Value\Resource;
use Hawk\AuthClient\Roles\Value\Role;
use Hawk\AuthClient\Users\Value\User;
use Hawk\AuthClient\Users\Value\UserConstraints;
use League\OAuth2\Client\Token\AccessToken;

/**
 * @extends AbstractEntityCache<User>
 * @internal This class is not part of the public API and may change at any time.
 */
class UserCache extends AbstractEntityCache
{
    protected UserFactory $userFactory;
    protected KeycloakApiClient $api;

    public function __construct(
        CacheAdapterInterface $cache,
        UserFactory           $userFactory,
        KeycloakApiClient     $api
    )
    {
        parent::__construct($cache);
        $this->userFactory = $userFactory;
        $this->api = $api;
    }

    /**
     * Uses an additional hop in the cache pool to resolve the user id by the token.
     *
     * @param AccessToken $token
     * @param callable():(User|null) $fallback
     * @return User|null
     */
    public function getOneByToken(AccessToken $token, callable $fallback): User|null
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
     * @return iterable<string>
     */
    public function getUserIdStream(UserConstraints|null $constraints): iterable
    {
        yield from $this->api->fetchUserIdStream($constraints, $this->cache);
    }

    /**
     * Returns a lazy stream of user ids that are members of the given group.
     * @param Group $group
     * @return iterable<string>
     */
    public function getGroupMemberIdStream(Group $group): iterable
    {
        yield from $this->api->fetchGroupMemberIdStream($group->getId(), $this->cache);
    }

    /**
     * Returns a lazy stream of user ids that are members of the given role.
     * @param Role $role
     * @return iterable<string>
     */
    public function getRoleMemberIdStream(Role $role): iterable
    {
        return $this->api->fetchRoleMemberIdStream($role->getId(), $this->cache);
    }

    /**
     * Returns a lazy stream of user ids that have access to the given resource.
     * @param Resource $resource
     * @param bool $includeOwner
     * @return iterable<array{int,array<string>}>
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
    #[\Override] protected function getCacheKey(string $id): string
    {
        return 'keycloak.user.by_id.' . $id;
    }

    /**
     * @inheritDoc
     */
    #[\Override] protected function fetchItems(string ...$ids): iterable
    {
        return $this->api->fetchUsersByIds(...$ids);
    }

    /**
     * @inheritDoc
     */
    #[\Override] protected function unserializeObject(string $id, array $data): object
    {
        return $this->userFactory->makeUserFromCacheData($data);
    }

    /**
     * @inheritDoc
     */
    #[\Override] protected function serializeObject(string $id, object $item): array
    {
        return $item->jsonSerialize();
    }
}
