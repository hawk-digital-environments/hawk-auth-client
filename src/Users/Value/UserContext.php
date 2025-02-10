<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Users\Value;


use Hawk\AuthClient\Container;
use Hawk\AuthClient\Groups\Value\GroupList;
use Hawk\AuthClient\Groups\Value\GroupReferenceList;
use Hawk\AuthClient\Profiles\Value\UserProfile;
use Hawk\AuthClient\Roles\Value\RoleList;
use Hawk\AuthClient\Roles\Value\RoleReferenceList;
use Hawk\AuthClient\Users\UserStorage;

/**
 * @internal This class is not part of the public API and may change without warning.
 */
class UserContext
{
    protected Container $container;
    protected User $user;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function getGroups(GroupReferenceList $groups): GroupList
    {
        return $this->container->getGroupStorage()->getAllInRefList($groups);
    }

    public function getRoles(RoleReferenceList $roles): RoleList
    {
        return $this->container->getRoleStorage()->getAllInRefList($roles);
    }

    public function getProfile(User $user): UserProfile
    {
        return $this->container->getProfileStorage()->getProfileOfUser($user);
    }

    public function getStorage(): UserStorage
    {
        return $this->container->getUserStorage();
    }
}
