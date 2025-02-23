<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Permissions;


use Hawk\AuthClient\Cache\CacheAdapterInterface;
use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Permissions\PermissionStorage;
use Hawk\AuthClient\Resources\ResourceStorage;
use Hawk\AuthClient\Resources\Value\Resource;
use Hawk\AuthClient\Resources\Value\ResourceScopes;
use Hawk\AuthClient\Tests\TestUtils\DummyUuid;
use Hawk\AuthClient\Users\Value\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PermissionStorage::class)]
class PermissionStorageTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new PermissionStorage(
            $this->createStub(CacheAdapterInterface::class),
            $this->createStub(KeycloakApiClient::class),
            $this->createStub(ResourceStorage::class)
        );
        $this->assertInstanceOf(PermissionStorage::class, $sut);
    }

    public function testItReturnsNullIfGrantedResourceScopesWereRequestedForNonExistingResource(): void
    {
        $resourceStorage = $this->createMock(ResourceStorage::class);
        $resourceStorage->expects($this->once())->method('getOne')->with('foo')->willReturn(null);
        $sut = new PermissionStorage(
            $this->createStub(CacheAdapterInterface::class),
            $this->createStub(KeycloakApiClient::class),
            $resourceStorage
        );
        $this->assertNull($sut->getGrantedResourceScopes('foo', $this->createStub(User::class)));
    }

    public function testItReturnsNullIfTheGivenResourceHasNoScopes(): void
    {
        $resource = $this->createStub(Resource::class);
        $resource->method('getScopes')->willReturn(null);
        $resourceStorage = $this->createMock(ResourceStorage::class);
        $resourceStorage->expects($this->never())->method('getOne');
        $sut = new PermissionStorage(
            $this->createStub(CacheAdapterInterface::class),
            $this->createStub(KeycloakApiClient::class),
            $resourceStorage
        );
        $this->assertNull($sut->getGrantedResourceScopes($resource, $this->createStub(User::class)));
    }

    public function testItResolvesGrantedScopes(): void
    {
        $resource = $this->createStub(Resource::class);
        $resource->method('getScopes')->willReturn(new ResourceScopes('foo', 'bar'));
        $scopes = new ResourceScopes('foo', 'bar');
        $cache = $this->createMock(CacheAdapterInterface::class);
        $cache->expects($this->once())->method('remember')->willReturn($scopes);

        $sut = new PermissionStorage(
            $cache,
            $this->createStub(KeycloakApiClient::class),
            $this->createStub(ResourceStorage::class)
        );

        $this->assertSame($scopes, $sut->getGrantedResourceScopes($resource, $this->createStub(User::class)));
        // It should be cached now (counter test will fail if it's not)
        $this->assertSame($scopes, $sut->getGrantedResourceScopes($resource, $this->createStub(User::class)));
    }

    public function testItStoresNonResolvedScopes(): void
    {
        $resource = $this->createStub(Resource::class);
        $resource->method('getScopes')->willReturn(new ResourceScopes('foo', 'bar'));
        $cache = $this->createMock(CacheAdapterInterface::class);
        $cache->expects($this->once())->method('remember')->willReturn(null);

        $sut = new PermissionStorage(
            $cache,
            $this->createStub(KeycloakApiClient::class),
            $this->createStub(ResourceStorage::class)
        );

        $this->assertNull($sut->getGrantedResourceScopes($resource, $this->createStub(User::class)));
        // It should be cached now (counter test will fail if it's not)
        $this->assertNull($sut->getGrantedResourceScopes($resource, $this->createStub(User::class)));
    }

    public function testItCachesCorrectly(): void
    {
        $resourceScopes = new ResourceScopes('foo', 'bar');
        $userScopes = new ResourceScopes('foo');
        $userId = new DummyUuid(1);
        $user = $this->createStub(User::class);
        $user->method('getId')->willReturn($userId);
        $ownerId = new DummyUuid(2);
        $owner = $this->createStub(User::class);
        $owner->method('getId')->willReturn($ownerId);
        $resourceId = new DummyUuid(3);
        $resource = $this->createStub(Resource::class);
        $resource->method('getId')->willReturn($resourceId);
        $resource->method('getScopes')->willReturn($resourceScopes);
        $resource->method('getOwner')->willReturn($owner);

        // Test lookup for owner
        $cache = $this->createMock(CacheAdapterInterface::class);
        $cache->expects($this->once())
            ->method('remember')
            ->willReturnCallback(function (
                string   $key,
                callable $valueGenerator,
                callable $valueToCache,
                callable $cacheToValue
            ) use ($resourceScopes, $ownerId, $resourceId) {
                $this->assertEquals('keycloak.client.permissions.' . $ownerId . '.' . $resourceId, $key);
                $this->assertSame($resourceScopes, $valueGenerator());
                $this->assertEquals(['foo', 'bar'], $valueToCache($resourceScopes));
                $this->assertFalse($valueToCache(null));
                $this->assertEquals($resourceScopes, $cacheToValue(['foo', 'bar']));
                $this->assertNull($cacheToValue(false));
                return $resourceScopes;
            });
        $sut = new PermissionStorage(
            $cache,
            $this->createStub(KeycloakApiClient::class),
            $this->createStub(ResourceStorage::class)
        );
        $this->assertSame($resourceScopes, $sut->getGrantedResourceScopes($resource, $owner));

        // Test lookup for user
        $cache = $this->createMock(CacheAdapterInterface::class);
        $cache->expects($this->once())
            ->method('remember')
            ->willReturnCallback(function (
                string   $key,
                callable $valueGenerator,
                callable $valueToCache,
                callable $cacheToValue
            ) use ($userScopes, $userId, $resourceId) {
                $this->assertEquals('keycloak.client.permissions.' . $userId . '.' . $resourceId, $key);
                $this->assertSame($userScopes, $valueGenerator());
                $this->assertEquals(['foo'], $valueToCache($userScopes));
                $this->assertFalse($valueToCache(null));
                $this->assertEquals($userScopes, $cacheToValue(['foo']));
                $this->assertNull($cacheToValue(false));
                return $userScopes;
            });
        $api = $this->createMock(KeycloakApiClient::class);
        $api->expects($this->once())
            ->method('fetchGrantedResourceScopesForUser')
            ->with($resource, $user)
            ->willReturn($userScopes);
        $sut = new PermissionStorage(
            $cache,
            $api,
            $this->createStub(ResourceStorage::class)
        );
        $this->assertSame($userScopes, $sut->getGrantedResourceScopes($resource, $user));
    }

    public function testItCanFlushTheResolvedValues(): void
    {
        $resource = $this->createStub(Resource::class);
        $resource->method('getScopes')->willReturn(new ResourceScopes('foo', 'bar'));
        $scopes = new ResourceScopes('foo', 'bar');
        $cache = $this->createMock(CacheAdapterInterface::class);
        $cache->expects($this->exactly(2))->method('remember')->willReturn($scopes);

        $sut = new PermissionStorage(
            $cache,
            $this->createStub(KeycloakApiClient::class),
            $this->createStub(ResourceStorage::class)
        );

        $this->assertSame($scopes, $sut->getGrantedResourceScopes($resource, $this->createStub(User::class)));

        $sut->flushResolved();

        $this->assertSame($scopes, $sut->getGrantedResourceScopes($resource, $this->createStub(User::class)));
    }

}
