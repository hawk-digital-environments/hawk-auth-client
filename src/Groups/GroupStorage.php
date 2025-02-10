<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Groups;


use Hawk\AuthClient\Cache\CacheAdapterInterface;
use Hawk\AuthClient\Groups\Value\Group;
use Hawk\AuthClient\Groups\Value\GroupList;
use Hawk\AuthClient\Groups\Value\GroupReference;
use Hawk\AuthClient\Groups\Value\GroupReferenceList;
use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Layers\GroupLayerInterface;

class GroupStorage implements GroupLayerInterface
{
    private CacheAdapterInterface $cache;
    private KeycloakApiClient $api;
    private GroupList|null $groups = null;

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
    #[\Override] public function getOne(\Stringable|string $identifier): Group|null
    {
        if (!$identifier instanceof GroupReference) {
            $identifier = new GroupReference((string)$identifier);
        }

        /** @noinspection LoopWhichDoesNotLoopInspection */
        foreach ($this->getAllInRefList(new GroupReferenceList($identifier)) as $group) {
            return $group;
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function getAllInRefList(GroupReferenceList $groupReferences): GroupList
    {
        $this->initialize();

        $collectedGroups = [];
        $requiredCount = $groupReferences->count();

        foreach ($this->groups->getRecursiveIterator() as $group) {
            if ($groupReferences->hasAny($group)) {
                $collectedGroups[] = $group;
                $requiredCount--;
            }

            if ($requiredCount <= 0) {
                break;
            }
        }

        return new GroupList(...$collectedGroups);
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function getAll(): GroupList
    {
        $this->initialize();
        return $this->groups;
    }

    /**
     * Removes all resolved groups and will fetch them again the next time they are requested.
     * @return void
     * @internal This method is not part of the public API and should not be used by client code.
     */
    public function flushResolved(): void
    {
        $this->groups = null;
    }

    private function initialize(): void
    {
        $this->groups ??= $this->cache->remember(
            'keycloak.groups',
            valueGenerator: fn() => $this->api->fetchGroups(),
            valueToCache: fn(GroupList $groups) => json_encode($groups),
            cacheToValue: fn(string $cachedGroups) => GroupList::fromScalarList(...json_decode($cachedGroups, true)),
        );
    }
}
