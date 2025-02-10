<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Resources;


use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Resources\ResourceCache;
use Hawk\AuthClient\Resources\ResourceStorage;
use Hawk\AuthClient\Resources\Value\Resource;
use Hawk\AuthClient\Resources\Value\ResourceConstraints;
use Hawk\AuthClient\Tests\TestUtils\PartialMockWithConstructorArgsTrait;
use Hawk\AuthClient\Users\UserStorage;
use Hawk\AuthClient\Users\Value\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ResourceStorage::class)]
class ResourceStorageTest extends TestCase
{
    use PartialMockWithConstructorArgsTrait;

    public function testItConstructs(): void
    {
        $sut = new ResourceStorage(
            $this->createStub(ResourceCache::class),
            $this->createStub(KeycloakApiClient::class),
            $this->createStub(UserStorage::class)
        );
        $this->assertInstanceOf(ResourceStorage::class, $sut);
    }

    public function testItCanGetOne(): void
    {
        $resource = $this->createStub(Resource::class);
        $cache = $this->createMock(ResourceCache::class);
        $cache->expects($this->once())
            ->method('getResourceId')
            ->with('foo')
            ->willReturn('foo-id');
        $cache->expects($this->once())
            ->method('getOne')
            ->with('foo-id')
            ->willReturn($resource);
        $sut = new ResourceStorage($cache, $this->createStub(KeycloakApiClient::class), $this->createStub(UserStorage::class));
        $this->assertSame($resource, $sut->getOne('foo'));
    }

    public function testItReturnsNullIfResourceIdCouldNotBeResolved(): void
    {
        $cache = $this->createMock(ResourceCache::class);
        $cache->expects($this->once())
            ->method('getResourceId')
            ->with('foo')
            ->willReturn(null);
        $sut = new ResourceStorage($cache, $this->createStub(KeycloakApiClient::class), $this->createStub(UserStorage::class));
        $this->assertNull($sut->getOne('foo'));
    }

    public function testItReturnsNullIfResourceCouldNotBeFound(): void
    {
        $cache = $this->createMock(ResourceCache::class);
        $cache->expects($this->once())
            ->method('getResourceId')
            ->with('foo')
            ->willReturn('foo-id');
        $cache->expects($this->once())
            ->method('getOne')
            ->with('foo-id')
            ->willReturn(null);
        $sut = new ResourceStorage($cache, $this->createStub(KeycloakApiClient::class), $this->createStub(UserStorage::class));
        $this->assertNull($sut->getOne('foo'));
    }

    public function testItCanGetAll(): void
    {
        $resource1 = $this->createStub(Resource::class);
        $resource2 = $this->createStub(Resource::class);
        $resource3 = $this->createStub(Resource::class);
        $constraints = $this->createStub(ResourceConstraints::class);
        $cache = $this->createMock(ResourceCache::class);
        $cache->expects($this->once())->method('getResourceIdStream')->with($constraints)->willReturn(['foo', 'bar', 'baz']);
        $cache->expects($this->once())->method('getAllByIds')->with('foo', 'bar', 'baz')->willReturn([$resource1, $resource2, $resource3]);

        $result = (new ResourceStorage($cache, $this->createStub(KeycloakApiClient::class), $this->createStub(UserStorage::class)))
            ->getAll($constraints);
        $this->assertSame([$resource1, $resource2, $resource3], iterator_to_array($result, false));
    }

    public function testItCanDefineANewResource(): void
    {
        $identifier = 'my-resource';
        $userStorage = $this->createStub(UserStorage::class);
        $api = $this->createStub(KeycloakApiClient::class);
        $cache = $this->createStub(ResourceCache::class);

        $sut = $this->createPartialMockWithConstructorArgs(
            ResourceStorage::class,
            ['getOne'],
            [$cache, $api, $userStorage]
        );

        $sut
            ->expects($this->once())
            ->method('getOne')
            ->with($identifier)
            ->willReturn(null);

        $builder = $sut->define($identifier);
        $this->assertEquals($identifier, $builder->getName());
        $this->assertFalse($builder->doesUpdateExistingResource());
    }

    public function testItCanDefineANewResourceWithAnExistingUuid(): void
    {
        $identifier = '72c97a1f-1ef9-4ba4-8a9d-3cd844488591';
        $expectedName = 'not-named-resource-' . hash('sha256', $identifier);
        $userStorage = $this->createStub(UserStorage::class);
        $api = $this->createStub(KeycloakApiClient::class);
        $cache = $this->createStub(ResourceCache::class);

        $sut = $this->createPartialMockWithConstructorArgs(
            ResourceStorage::class,
            ['getOne'],
            [$cache, $api, $userStorage]
        );

        $sut
            ->expects($this->once())
            ->method('getOne')
            ->with($identifier)
            ->willReturn(null);

        $builder = $sut->define($identifier);
        $this->assertEquals($expectedName, $builder->getName());
        $this->assertFalse($builder->doesUpdateExistingResource());
    }

    public function testItCanUpdateAnExistingResource(): void
    {
        $resource = new Resource(
            id: '83335934-fc49-4c59-8199-de47c3d03ac5',
            name: 'name',
            displayName: 'displayName',
            ownerId: '83335934-fc49-4c59-8199-de47c3d03ac3',
            isUserManaged: true,
            attributes: [],
            iconUri: null,
            uris: [],
            scopes: null,
            type: null,
            userStorage: $this->createStub(UserStorage::class)
        );

        $sut = new ResourceStorage(
            $this->createStub(ResourceCache::class),
            $this->createStub(KeycloakApiClient::class),
            $this->createStub(UserStorage::class)
        );

        $builder = $sut->define($resource);
        $this->assertEquals($resource->getId(), $builder->getId());
        $this->assertEquals($resource->getName(), $builder->getName());
        $this->assertEquals($resource->getDisplayName(), $builder->getDisplayName());
        $this->assertTrue($builder->doesUpdateExistingResource());
    }

    public function testItCanRemoveAResource(): void
    {
        $resource = $this->createStub(Resource::class);
        $resource->method('getId')->willReturn('foo');
        $cache = $this->createMock(ResourceCache::class);
        $cache->expects($this->once())
            ->method('remove')
            ->with('foo');
        $sut = new ResourceStorage($cache, $this->createStub(KeycloakApiClient::class), $this->createStub(UserStorage::class));
        $sut->remove($resource);
    }

    public function testItCanShareAResource(): void
    {
        $resource = $this->createStub(Resource::class);
        $user = $this->createStub(User::class);
        $scopes = ['foo', 'bar', 'baz'];

        $api = $this->createMock(KeycloakApiClient::class);
        $api->expects($this->once())
            ->method('setResourceUserPermissions')
            ->with($resource, $user, $scopes);

        $sut = new ResourceStorage($this->createStub(ResourceCache::class), $api, $this->createStub(UserStorage::class));
        $sut->shareWithUser($resource, $user, $scopes);
    }
}
