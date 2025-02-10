<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Permissions;


use Hawk\AuthClient\Exception\UserToGuardNotFoundException;
use Hawk\AuthClient\Layers\GuardLayerInterface;
use Hawk\AuthClient\Users\UserStorage;
use Hawk\AuthClient\Users\Value\User;

class GuardFactory implements GuardLayerInterface
{
    private PermissionStorage $permissionStorage;
    private UserStorage $userStorage;

    public function __construct(
        PermissionStorage $permissionStorage,
        UserStorage       $userStorage
    )
    {
        $this->permissionStorage = $permissionStorage;
        $this->userStorage = $userStorage;
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function getOne(string|\Stringable|User $user): Guard
    {
        if (!$user instanceof User) {
            $resolvedUser = $this->userStorage->getOne($user);
            if ($resolvedUser === null) {
                throw new UserToGuardNotFoundException((string)$user);
            }
            $user = $resolvedUser;
        }

        return new Guard($user, $this->permissionStorage);
    }
}
