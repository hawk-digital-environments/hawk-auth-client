<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Layers;


use Hawk\AuthClient\Roles\Value\Role;
use Hawk\AuthClient\Roles\Value\RoleList;
use Hawk\AuthClient\Roles\Value\RoleReferenceList;

interface RoleLayerInterface
{
    /**
     * Retrieves a single role by its identifier. Returns null if the role does not exist.
     *
     * @param \Stringable|string $identifier The identifier may be, the role's name or uuid.
     * @return Role|null
     */
    public function getOne(string|\Stringable $identifier): Role|null;

    /**
     * Retrieves a list of roles by their references. Only includes roles that exist.
     *
     * @param RoleReferenceList $roleReferences
     * @return RoleList
     */
    public function getAllInRefList(RoleReferenceList $roleReferences): RoleList;

    /**
     * Returns a list of all roles.
     *
     * @return RoleList
     */
    public function getAll(): RoleList;
}
