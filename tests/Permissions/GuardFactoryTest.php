<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Permissions;


use Hawk\AuthClient\Exception\UserToGuardNotFoundException;
use Hawk\AuthClient\Permissions\Guard;
use Hawk\AuthClient\Permissions\GuardFactory;
use Hawk\AuthClient\Permissions\PermissionStorage;
use Hawk\AuthClient\Users\UserStorage;
use Hawk\AuthClient\Users\Value\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GuardFactory::class)]
#[CoversClass(UserToGuardNotFoundException::class)]
class GuardFactoryTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new GuardFactory($this->createStub(PermissionStorage::class), $this->createStub(UserStorage::class));
        $this->assertInstanceOf(GuardFactory::class, $sut);
    }

    public function testItCanCreateAGuardWithAValidUser(): void
    {
        $user = $this->createStub(User::class);
        $sut = new GuardFactory($this->createStub(PermissionStorage::class), $this->createStub(UserStorage::class));
        $this->assertInstanceOf(Guard::class, $sut->getOne($user));
    }

    public function testItCanCreateAGuardWithAUserId(): void
    {
        $userId = '83335934-fc49-4c59-8199-de47c3d03ac5';
        $user = $this->createStub(User::class);
        $userStorage = $this->createMock(UserStorage::class);
        $userStorage->expects($this->once())->method('getOne')->with($userId)->willReturn($user);
        $sut = new GuardFactory($this->createStub(PermissionStorage::class), $userStorage);
        $this->assertInstanceOf(Guard::class, $sut->getOne($userId));
    }


    public function testItFailsToCreateAGuardWithAUserIdThatDoesNotBelongToAUser(): void
    {
        $userId = '83335934-fc49-4c59-8199-de47c3d03ac5';
        $userStorage = $this->createMock(UserStorage::class);
        $userStorage->expects($this->once())->method('getOne')->with($userId)->willReturn(null);
        $sut = new GuardFactory($this->createStub(PermissionStorage::class), $userStorage);

        $this->expectException(UserToGuardNotFoundException::class);
        $sut->getOne($userId);
    }
}
