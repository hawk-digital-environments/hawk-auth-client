<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Layers;


use Hawk\AuthClient\Groups\Value\Group;
use Hawk\AuthClient\Groups\Value\GroupList;
use Hawk\AuthClient\Groups\Value\GroupReferenceList;

interface GroupLayerInterface
{
    /**
     * Retrieves a single group by its identifier. Returns null if the group does not exist.
     *
     * @param string|\Stringable $identifier The identifier may be, the group's name, path or uuid.
     *
     * @return Group|null
     */
    public function getOne(string|\Stringable $identifier): Group|null;

    /**
     * Retrieves a list of groups by their references. Only includes groups that exist.
     *
     * @param GroupReferenceList $groupReferences
     *
     * @return GroupList
     */
    public function getAllInRefList(GroupReferenceList $groupReferences): GroupList;

    /**
     * Returns a list of all groups.
     *
     * @return GroupList
     */
    public function getAll(): GroupList;
}
