<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Layers;


use Hawk\AuthClient\Groups\Value\Group;
use Hawk\AuthClient\Resources\Value\Resource;
use Hawk\AuthClient\Roles\Value\Role;
use Hawk\AuthClient\Users\Value\ResourceUserList;
use Hawk\AuthClient\Users\Value\User;
use Hawk\AuthClient\Users\Value\UserConstraints;
use Hawk\AuthClient\Users\Value\UserList;

interface UserLayerInterface
{
    /**
     * Retrieves a single user by its identifier. Returns null if the user does not exist.
     * @param string|\Stringable $userId The users UUID.
     * @return User|null
     */
    public function getOne(string|\Stringable $userId): User|null;

    /**
     * Returns a list of all users that are available on the auth server.
     * If no constraints are provided, all users are returned.
     * @param UserConstraints|null $constraints A set of constraints to filter the users.
     * @return UserList
     */
    public function getAll(UserConstraints|null $constraints = null): UserList;

    /**
     * Returns a list of all users that are members of the provided group.
     * Only direct members are included, not members of subgroups.
     *
     * @param Group $group The group to retrieve the members from.
     * @return UserList
     */
    public function getGroupMembers(Group $group): UserList;

    /**
     * Returns a list of all users that are members of the provided role.
     * @param Role $role The role to retrieve the members from.
     * @return UserList
     */
    public function getRoleMembers(Role $role): UserList;

    /**
     * Returns a list of all users that have access to the provided resource.
     * The list contains {@see ResourceUser} objects that enhance the normal {@see User} with the allowed scopes.
     *
     * @param Resource $resource The resource to retrieve the users from.
     * @param bool $includeOwner If true, the owner of the resource is included in the list.
     * @return ResourceUserList
     */
    public function getResourceUsers(Resource $resource, bool $includeOwner = false): ResourceUserList;
}
