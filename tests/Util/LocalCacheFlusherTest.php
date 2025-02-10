<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Util;


use Hawk\AuthClient\Container;
use Hawk\AuthClient\Groups\GroupStorage;
use Hawk\AuthClient\Keycloak\CacheBusterStorage;
use Hawk\AuthClient\Permissions\PermissionStorage;
use Hawk\AuthClient\Profiles\ProfileStorage;
use Hawk\AuthClient\Resources\ResourceCache;
use Hawk\AuthClient\Roles\RoleStorage;
use Hawk\AuthClient\Users\UserCache;
use Hawk\AuthClient\Util\LocalCacheFlusher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LocalCacheFlusher::class)]
class LocalCacheFlusherTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new LocalCacheFlusher($this->createStub(Container::class));
        $this->assertInstanceOf(LocalCacheFlusher::class, $sut);
    }

    public function testItFlushesTheCache(): void
    {
        $container = $this->createMock(Container::class);

        $cacheBusterStorage = $this->createMock(CacheBusterStorage::class);
        $cacheBusterStorage->expects($this->once())->method('flush');
        $container->method('getCacheBusterStorage')->willReturn($cacheBusterStorage);
        $container->expects($this->once())->method('getCacheBusterStorage');

        $resourceCache = $this->createMock(ResourceCache::class);
        $resourceCache->expects($this->once())->method('flushResolved');
        $container->method('getResourceCache')->willReturn($resourceCache);
        $container->expects($this->once())->method('getResourceCache');

        $userCache = $this->createMock(UserCache::class);
        $userCache->expects($this->once())->method('flushResolved');
        $container->method('getUserCache')->willReturn($userCache);
        $container->expects($this->once())->method('getUserCache');

        $roleStorage = $this->createMock(RoleStorage::class);
        $roleStorage->expects($this->once())->method('flushResolved');
        $container->method('getRoleStorage')->willReturn($roleStorage);
        $container->expects($this->once())->method('getRoleStorage');

        $groupStorage = $this->createMock(GroupStorage::class);
        $groupStorage->expects($this->once())->method('flushResolved');
        $container->method('getGroupStorage')->willReturn($groupStorage);
        $container->expects($this->once())->method('getGroupStorage');

        $profileStorage = $this->createMock(ProfileStorage::class);
        $profileStorage->expects($this->once())->method('flushResolved');
        $container->method('getProfileStorage')->willReturn($profileStorage);
        $container->expects($this->once())->method('getProfileStorage');

        $permissionStorage = $this->createMock(PermissionStorage::class);
        $permissionStorage->expects($this->once())->method('flushResolved');
        $container->method('getPermissionStorage')->willReturn($permissionStorage);
        $container->expects($this->once())->method('getPermissionStorage');

        (new LocalCacheFlusher($container))->flushCache();
    }

}
