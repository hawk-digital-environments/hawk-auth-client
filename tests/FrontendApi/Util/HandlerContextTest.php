<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\FrontendApi\Util;


use Hawk\AuthClient\Auth\KeycloakProvider;
use Hawk\AuthClient\Container;
use Hawk\AuthClient\FrontendApi\Util\CurrentUserFinder;
use Hawk\AuthClient\FrontendApi\Util\HandlerContext;
use Hawk\AuthClient\Keycloak\CacheBusterStorage;
use Hawk\AuthClient\Keycloak\Value\CacheBuster;
use Hawk\AuthClient\Permissions\PermissionStorage;
use Hawk\AuthClient\Users\UserStorage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HandlerContext::class)]
class HandlerContextTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new HandlerContext($this->createStub(Container::class));
        $this->assertInstanceOf(HandlerContext::class, $sut);
    }

    public function testItCanGetTheKeycloakProvider(): void
    {
        $provider = $this->createStub(KeycloakProvider::class);
        $container = $this->createMock(Container::class);
        $container->expects($this->once())->method('getKeycloakOauthProvider')->willReturn($provider);

        $sut = new HandlerContext($container);

        $this->assertSame($provider, $sut->getKeycloakProvider());
    }

    public function testItCanGetTheUserStorage(): void
    {
        $storage = $this->createStub(UserStorage::class);
        $container = $this->createMock(Container::class);
        $container->expects($this->once())->method('getUserStorage')->willReturn($storage);

        $sut = new HandlerContext($container);

        $this->assertSame($storage, $sut->getUserStorage());
    }

    public function testItCanGetTheCacheBuster(): void
    {
        $cacheBuster = $this->createStub(CacheBuster::class);
        $storage = $this->createStub(CacheBusterStorage::class);
        $storage->method('getCacheBuster')->willReturn($cacheBuster);
        $container = $this->createMock(Container::class);
        $container->expects($this->once())->method('getCacheBusterStorage')->willReturn($storage);

        $sut = new HandlerContext($container);

        $this->assertSame($cacheBuster, $sut->getCacheBuster());
    }

    public function testItCanGetThePermissionStorage(): void
    {
        $storage = $this->createStub(PermissionStorage::class);
        $container = $this->createMock(Container::class);
        $container->expects($this->once())->method('getPermissionStorage')->willReturn($storage);

        $sut = new HandlerContext($container);

        $this->assertSame($storage, $sut->getPermissionStorage());
    }

    public function testItCanGetCurrentUserFinder(): void
    {
        $storage = $this->createStub(UserStorage::class);
        $keycloakProvider = $this->createStub(KeycloakProvider::class);

        $container = $this->createMock(Container::class);
        $container->expects($this->once())->method('getUserStorage')->willReturn($storage);
        $container->expects($this->once())->method('getKeycloakOauthProvider')->willReturn($keycloakProvider);

        $sut = new HandlerContext($container);

        $this->assertInstanceOf(CurrentUserFinder::class, $sut->getCurrentUserFinder());
    }

}
