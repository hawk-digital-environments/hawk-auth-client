<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Roles;


use Hawk\AuthClient\Cache\CacheAdapterInterface;
use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Layers\RoleLayerInterface;
use Hawk\AuthClient\Roles\Value\Role;
use Hawk\AuthClient\Roles\Value\RoleList;
use Hawk\AuthClient\Roles\Value\RoleReference;
use Hawk\AuthClient\Roles\Value\RoleReferenceList;

class RoleStorage implements RoleLayerInterface
{
    protected CacheAdapterInterface $cache;
    protected KeycloakApiClient $api;
    protected RoleList|null $roles = null;

    public function __construct(
        CacheAdapterInterface $cache,
        KeycloakApiClient     $api
    )
    {
        $this->cache = $cache;
        $this->api = $api;
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function getOne(\Stringable|string $identifier): Role|null
    {
        if (!$identifier instanceof RoleReference) {
            $identifier = new RoleReference((string)$identifier);
        }

        /** @noinspection LoopWhichDoesNotLoopInspection */
        foreach ($this->getAllInRefList(new RoleReferenceList($identifier)) as $role) {
            return $role;
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function getAllInRefList(RoleReferenceList $roleReferences): RoleList
    {
        $this->initialize();

        $collectedRoles = [];
        $requiredCount = $roleReferences->count();
        foreach ($this->roles as $role) {
            if ($roleReferences->hasAny($role)) {
                $collectedRoles[] = $role;
                $requiredCount--;
            }

            if ($requiredCount === 0) {
                break;
            }
        }

        return new RoleList(...$collectedRoles);
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function getAll(): RoleList
    {
        $this->initialize();
        return $this->roles;
    }

    /**
     * Removes all resolved roles and will fetch them again the next time they are requested.
     * @return void
     * @internal This method is not part of the public API and should not be used by client code.
     */
    public function flushResolved(): void
    {
        $this->roles = null;
    }

    protected function initialize(): void
    {
        $this->roles ??= $this->cache->remember(
            'keycloak.roles',
            valueGenerator: fn() => $this->api->fetchRoles(),
            valueToCache: fn(RoleList $roles) => json_encode($roles),
            cacheToValue: fn(string $data) => RoleList::fromScalarList(...json_decode($data, true)),
        );
    }
}
