<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Users;


use Hawk\AuthClient\Groups\Value\Group;
use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Layers\UserLayerInterface;
use Hawk\AuthClient\Resources\Value\Resource;
use Hawk\AuthClient\Roles\Value\Role;
use Hawk\AuthClient\Users\Value\ResourceUser;
use Hawk\AuthClient\Users\Value\ResourceUserList;
use Hawk\AuthClient\Users\Value\User;
use Hawk\AuthClient\Users\Value\UserConstraints;
use Hawk\AuthClient\Users\Value\UserList;
use League\OAuth2\Client\Token\AccessToken;

class UserStorage implements UserLayerInterface
{
    protected KeycloakApiClient $api;
    protected UserCache $userCache;

    public function __construct(
        KeycloakApiClient $api,
        UserCache         $userCache
    )
    {
        $this->api = $api;
        $this->userCache = $userCache;
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function getOne(string|\Stringable $userId): User|null
    {
        return $this->userCache->getOne((string)$userId);
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function getAll(?UserConstraints $constraints = null): UserList
    {
        return new UserList(
            fn() => $this->userCache->getUserIdStream($constraints),
            [$this->userCache, 'getAllByIds']
        );
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function getGroupMembers(Group $group): UserList
    {
        return new UserList(
            fn() => $this->userCache->getGroupMemberIdStream($group),
            [$this->userCache, 'getAllByIds']
        );
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function getRoleMembers(Role $role): UserList
    {
        return new UserList(
            fn() => $this->userCache->getRoleMemberIdStream($role),
            [$this->userCache, 'getAllByIds']
        );
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function getResourceUsers(Resource $resource, bool $includeOwner = false): ResourceUserList
    {
        $userIdToScopes = [];
        return new ResourceUserList(
            function () use (&$userIdToScopes, $resource, $includeOwner) {
                foreach ($this->userCache->getResourceUserIdStream($resource, $includeOwner) as $val) {
                    [$userId, $scopes] = $val;
                    $userIdToScopes[$userId] = $scopes;
                    yield $userId;
                }
            },
            function (string ...$ids) use (&$userIdToScopes) {
                foreach ($this->userCache->getAllByIds(...$ids) as $user) {
                    yield ResourceUser::fromUserAndScopes($user, $userIdToScopes[$user->getId()] ?? []);
                }
            }
        );
    }

    /**
     * Returns the user associated with the given access token.
     * May return null if the user is not found.
     * @param AccessToken $token
     * @param callable $fallback
     * @return User|null
     * @internal This method is not part of the public api and may be removed in future versions.
     */
    public function getOneByToken(AccessToken $token, callable $fallback): User|null
    {
        return $this->userCache->getOneByToken($token, $fallback);
    }
}
