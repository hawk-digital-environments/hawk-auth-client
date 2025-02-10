<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Permissions;


use Hawk\AuthClient\Groups\Value\Group;
use Hawk\AuthClient\Groups\Value\GroupReference;
use Hawk\AuthClient\Resources\Value\Resource;
use Hawk\AuthClient\Roles\Value\Role;
use Hawk\AuthClient\Roles\Value\RoleReference;
use Hawk\AuthClient\Users\Value\User;

class Guard
{
    private User $user;
    private PermissionStorage $permissions;

    public function __construct(
        User              $user,
        PermissionStorage $permissions
    )
    {
        $this->user = $user;
        $this->permissions = $permissions;
    }

    /**
     * Checks if the user has any one of the given roles. The roles can be given as string, {@see RoleReference} or {@see Role}.
     * @param string|\Stringable|RoleReference|Role ...$roles
     * @return bool
     */
    public function hasAnyRole(string|\Stringable|RoleReference|Role ...$roles): bool
    {
        return $this->user->getRoleReferences()->hasAny(...$roles);
    }

    /**
     * Checks if the user has any one of the given groups. The groups can be given as string, {@see GroupReference} or {@see Group}.
     * This method checks for exact matches only, the hierarchy is not considered.
     * @param string|\Stringable|GroupReference|Group ...$groups
     * @return bool
     */
    public function hasAnyGroup(string|\Stringable|GroupReference|Group ...$groups): bool
    {
        return $this->user->getGroupReferences()->hasAny(...$groups);
    }

    /**
     * Checks if the user has any one of the given groups or any child of the given groups.
     * The groups can be given as string, {@see GroupReference} or {@see Group}.
     *
     * @param string|\Stringable|GroupReference|Group ...$groups
     * @return bool
     */
    public function hasAnyGroupOrHasChildOfAny(string|\Stringable|GroupReference|Group ...$groups): bool
    {
        return $this->user->getGroupReferences()->hasAnyOrHasChildOfAny(...$groups);
    }

    /**
     * Checks if the user has any of the given scopes on the given resource.
     * Each resource can have fine-grained permissions, for any kind of action you can imagine.
     * Common scopes are "read", "write", "delete", "admin" etc.
     * If the user has no permissions on the given resource, false is returned.
     *
     * @param string|\Stringable|Resource $resource The resource to check the permissions for.
     *                              Can be either a {@see Resource} object, a resource uuid or name.
     * @param string ...$scopes A list of scopes to check for. If this list is empty, this method will return true as long as the user has any scope on the resource.
     * @return bool
     */
    public function hasAnyResourceScope(string|\Stringable|Resource $resource, string ...$scopes): bool
    {
        return $this->permissions->getGrantedResourceScopes($resource, $this->user)?->hasAny(...$scopes) ?? false;
    }

    /**
     * Checks if the user has all given scopes on the given resource.
     * Each resource can have fine-grained permissions, for any kind of action you can imagine.
     * Common scopes are "read", "write", "delete", "admin" etc.
     * If the user has no permissions on the given resource, false is returned.
     *
     * @param string|\Stringable|Resource $resource The resource to check the permissions for.
     *                              Can be either a {@see Resource} object, a resource uuid or name.
     * @param string ...$scopes A list of scopes to check for. If this list is empty, this method will return false.
     * @return bool
     */
    public function hasAllResourceScopes(string|\Stringable|Resource $resource, string ...$scopes): bool
    {
        return $this->permissions->getGrantedResourceScopes($resource, $this->user)?->hasAll(...$scopes) ?? false;
    }
}
