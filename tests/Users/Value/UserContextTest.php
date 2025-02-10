<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Users\Value;


use Hawk\AuthClient\Container;
use Hawk\AuthClient\Groups\GroupStorage;
use Hawk\AuthClient\Groups\Value\GroupList;
use Hawk\AuthClient\Groups\Value\GroupReferenceList;
use Hawk\AuthClient\Profiles\ProfileStorage;
use Hawk\AuthClient\Profiles\Value\UserProfile;
use Hawk\AuthClient\Roles\RoleStorage;
use Hawk\AuthClient\Roles\Value\RoleList;
use Hawk\AuthClient\Roles\Value\RoleReferenceList;
use Hawk\AuthClient\Users\UserStorage;
use Hawk\AuthClient\Users\Value\User;
use Hawk\AuthClient\Users\Value\UserContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UserContext::class)]
class UserContextTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new UserContext($this->createStub(Container::class));
        $this->assertInstanceOf(UserContext::class, $sut);
    }

    public function testItCanGetGroups(): void
    {
        $groupReferenceList = $this->createStub(GroupReferenceList::class);
        $groupList = $this->createStub(GroupList::class);
        $groupStorage = $this->createMock(GroupStorage::class);
        $groupStorage->method('getAllInRefList')->with($groupReferenceList)->willReturn($groupList);
        $container = $this->createStub(Container::class);
        $container->method('getGroupStorage')->willReturn($groupStorage);
        $sut = new UserContext($container);
        $this->assertSame($groupList, $sut->getGroups($groupReferenceList));
    }

    public function testItCanGetRoles(): void
    {
        $roleReferenceList = $this->createStub(RoleReferenceList::class);
        $roleList = $this->createStub(RoleList::class);
        $roleStorage = $this->createMock(RoleStorage::class);
        $roleStorage->method('getAllInRefList')->with($roleReferenceList)->willReturn($roleList);
        $container = $this->createStub(Container::class);
        $container->method('getRoleStorage')->willReturn($roleStorage);
        $sut = new UserContext($container);
        $this->assertSame($roleList, $sut->getRoles($roleReferenceList));
    }

    public function testItCanGetAUsersProfile(): void
    {
        $user = $this->createStub(User::class);
        $profile = $this->createStub(UserProfile::class);
        $profileStorage = $this->createMock(ProfileStorage::class);
        $profileStorage->method('getProfileOfUser')->with($user)->willReturn($profile);
        $container = $this->createStub(Container::class);
        $container->method('getProfileStorage')->willReturn($profileStorage);
        $sut = new UserContext($container);
        $this->assertSame($profile, $sut->getProfile($user));
    }

    public function testItCanGetTheUserStorage(): void
    {
        $userStorage = $this->createStub(UserStorage::class);
        $container = $this->createStub(Container::class);
        $container->method('getUserStorage')->willReturn($userStorage);
        $sut = new UserContext($container);
        $this->assertSame($userStorage, $sut->getStorage());
    }
}
