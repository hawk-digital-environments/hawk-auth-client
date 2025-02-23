<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Users;


use Hawk\AuthClient\Exception\MissingHawkUserClaimException;
use Hawk\AuthClient\Exception\MissingUserIdClaimException;
use Hawk\AuthClient\Groups\Value\GroupReferenceList;
use Hawk\AuthClient\Keycloak\Value\ConnectionConfig;
use Hawk\AuthClient\Roles\Value\RoleReferenceList;
use Hawk\AuthClient\Users\Value\User;
use Hawk\AuthClient\Users\Value\UserClaims;
use Hawk\AuthClient\Users\Value\UserContext;
use Hawk\AuthClient\Util\Uuid;

class UserFactory
{
    protected UserContext $context;

    public function __construct(
        ConnectionConfig $config,
        UserContext      $context
    )
    {
        $this->config = $config;
        $this->context = $context;
    }

    protected ConnectionConfig $config;

    public function makeUserFromKeycloakData(array $data): User
    {
        $id = $data['sub'] ?? '';
        if (empty($id)) {
            throw new MissingUserIdClaimException();
        }

        $id = new Uuid($id);

        if (!is_array($data['hawk'] ?? null)) {
            throw new MissingHawkUserClaimException($id);
        }

        $claims = $data;
        unset(
            $claims['sub'],
            $claims['hawk'],
            $claims['groups'],
            $claims['realm_access'],
            $claims['resource_access'],
            $claims['preferred_username']
        );

        return new User(
            $id,
            $data['preferred_username'] ?? '',
            new UserClaims($claims),
            RoleReferenceList::fromScalarList(...$this->filterRoles(
                ...array_merge(
                $data['hawk']['roles']['realm'] ?? [],
                $data['hawk']['roles']['client'][$this->config->getClientId()] ?? []
            ))),
            GroupReferenceList::fromScalarList(...$this->filterGroups(
                ...($data['hawk']['groups'] ?? [])
            )),
            $this->context
        );
    }

    public function makeUserFromCacheData(array $data): User
    {
        return new User(
            new Uuid($data[User::ARRAY_KEY_ID]),
            $data[User::ARRAY_KEY_USERNAME],
            new UserClaims($data[User::ARRAY_KEY_CLAIMS]),
            RoleReferenceList::fromScalarList(...$data[User::ARRAY_KEY_ROLES]),
            GroupReferenceList::fromScalarList(...$data[User::ARRAY_KEY_GROUPS]),
            $this->context
        );
    }

    protected function filterRoles(...$roles): array
    {
        static $ignoredRoles = [
            'offline_access',
            'uma_authorization',
            'uma_protection'
        ];

        return array_values(
            array_filter(
                array_unique($roles),
                static fn($role) => is_string($role)
                    && !in_array($role, $ignoredRoles, true)
                    && !str_starts_with($role, 'default-roles-')
            )
        );
    }

    protected function filterGroups(...$groups): array
    {
        static $ignoredGroups = [
            'offline_access'
        ];

        return array_values(
            array_filter(
                array_unique($groups),
                static fn($group) => is_string($group)
                    && !in_array($group, $ignoredGroups, true)
            )
        );
    }
}
