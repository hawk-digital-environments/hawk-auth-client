<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Users;


use Hawk\AuthClient\Cache\CacheAdapterInterface;
use Hawk\AuthClient\Groups\Value\Group;
use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Resources\Value\Resource;
use Hawk\AuthClient\Resources\Value\ResourceScopes;
use Hawk\AuthClient\Roles\Value\Role;
use Hawk\AuthClient\Tests\TestUtils\PartialMockWithConstructorArgsTrait;
use Hawk\AuthClient\Tests\TestUtils\TestCacheAdapter;
use Hawk\AuthClient\Users\UserCache;
use Hawk\AuthClient\Users\UserFactory;
use Hawk\AuthClient\Users\Value\User;
use Hawk\AuthClient\Users\Value\UserConstraints;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UserCache::class)]
class UserCacheTest extends TestCase
{
    use PartialMockWithConstructorArgsTrait;

    public function testItConstructs(): void
    {
        $sut = new UserCache(
            $this->createStub(CacheAdapterInterface::class),
            $this->createStub(UserFactory::class),
            $this->createStub(KeycloakApiClient::class)
        );
        $this->assertInstanceOf(UserCache::class, $sut);
    }

    public function testItCanGetASingleUserByToken(): void
    {
        $cache = $this->createMock(CacheAdapterInterface::class);
        $cache->expects($this->once())
            ->method('remember')
            ->willReturn('f47ac10b-58cc-4372-a567-0e02b2c3d001');

        $sut = $this->createPartialMockWithConstructorArgs(UserCache::class, ['getOne'], [
            $cache,
            $this->createStub(UserFactory::class),
            $this->createStub(KeycloakApiClient::class)
        ]);
        $sut->expects($this->once())
            ->method('getOne')
            ->with('f47ac10b-58cc-4372-a567-0e02b2c3d001')
            ->willReturn($this->createStub(User::class));

        $this->assertInstanceOf(User::class, $sut->getOneByToken(
            new AccessToken(['access_token' => 'foo']),
            fn() => $this->fail('Should not be called')
        ));
    }

    public function testItReturnsNullIfItCanNotResolveAUserByToken(): void
    {
        $cache = $this->createMock(CacheAdapterInterface::class);
        $cache->expects($this->once())
            ->method('remember')
            ->willReturn(null);

        $sut = $this->createPartialMockWithConstructorArgs(UserCache::class, ['getOne'], [
            $cache,
            $this->createStub(UserFactory::class),
            $this->createStub(KeycloakApiClient::class)
        ]);
        $sut->expects($this->never())->method('getOne');

        $this->assertNull($sut->getOneByToken(
            new AccessToken(['access_token' => 'foo']),
            fn() => $this->fail('Should not be called')
        ));
    }

    public function testItCachesTheAccessTokenToIdMappingCorrectly(): void
    {
        $cache = $this->createMock(CacheAdapterInterface::class);
        $cache->expects($this->once())
            ->method('remember')
            ->willReturnCallback(function (
                string   $key,
                callable $valueGenerator,
                         $_,
                         $_1,
                int      $ttl
            ) {
                $this->assertEquals('keycloak.user.by_token.' . hash('sha256', 'foo'), $key);
                $this->assertEquals('TEST_ID_VALUE', $valueGenerator());
                $this->assertEquals(60 * 15, $ttl);
                return 'f47ac10b-58cc-4372-a567-0e02b2c3d001';
            });

        $sut = $this->createPartialMockWithConstructorArgs(UserCache::class, ['getOne'], [
            $cache,
            $this->createStub(UserFactory::class),
            $this->createStub(KeycloakApiClient::class)
        ]);
        $sut->expects($this->once())
            ->method('getOne')
            ->with('f47ac10b-58cc-4372-a567-0e02b2c3d001')
            ->willReturn($this->createStub(User::class));

        $this->assertInstanceOf(User::class, $sut->getOneByToken(
            new AccessToken(['access_token' => 'foo']),
            function () {
                $user = $this->createStub(User::class);
                $user->method('getId')->willReturn('TEST_ID_VALUE');
                return $user;
            }
        ));
    }

    public function testItCanReturnTheUserIdStreamWithNull(): void
    {
        $cache = $this->createStub(CacheAdapterInterface::class);
        $api = $this->createMock(KeycloakApiClient::class);
        $api->expects($this->once())
            ->method('fetchUserIdStream')
            ->with(null, $cache)
            ->willReturn([]);
        $sut = new UserCache($cache, $this->createStub(UserFactory::class), $api);
        $this->assertEquals([], iterator_to_array($sut->getUserIdStream(null), false));
    }

    public function testItCanReturnTheUserIdStreamWithConstraints(): void
    {
        $constraints = $this->createStub(UserConstraints::class);
        $cache = $this->createStub(CacheAdapterInterface::class);
        $api = $this->createMock(KeycloakApiClient::class);
        $api->expects($this->once())
            ->method('fetchUserIdStream')
            ->with($constraints, $cache)
            ->willReturn(['foo', 'bar', 'baz']);
        $sut = new UserCache($cache, $this->createStub(UserFactory::class), $api);
        $this->assertEquals(['foo', 'bar', 'baz'], iterator_to_array($sut->getUserIdStream($constraints), false));
    }

    public function testItCanReturnTheGroupMemberIdStream(): void
    {
        $group = $this->createStub(Group::class);
        $cache = $this->createStub(CacheAdapterInterface::class);
        $api = $this->createMock(KeycloakApiClient::class);
        $api->expects($this->once())
            ->method('fetchGroupMemberIdStream')
            ->with($group->getId(), $cache)
            ->willReturn(['foo', 'bar', 'baz']);
        $sut = new UserCache($cache, $this->createStub(UserFactory::class), $api);
        $this->assertEquals(['foo', 'bar', 'baz'], iterator_to_array($sut->getGroupMemberIdStream($group), false));
    }

    public function testItCanReturnTheRoleMemberStream(): void
    {
        $role = $this->createStub(Role::class);
        $cache = $this->createStub(CacheAdapterInterface::class);
        $api = $this->createMock(KeycloakApiClient::class);
        $api->expects($this->once())
            ->method('fetchRoleMemberIdStream')
            ->with($role->getId(), $cache)
            ->willReturn(['foo', 'bar', 'baz']);
        $sut = new UserCache($cache, $this->createStub(UserFactory::class), $api);
        $this->assertEquals(['foo', 'bar', 'baz'], iterator_to_array($sut->getRoleMemberIdStream($role), false));
    }

    public function testItCanReturnTheResourceUserIdStreamWithoutTheOwner(): void
    {
        $result = [['foo', ['scope']], ['bar', ['barscope', 'bazscope']]];
        $resource = $this->createMock(Resource::class);
        $resource->expects($this->never())->method('getOwner');
        $cache = $this->createStub(CacheAdapterInterface::class);
        $api = $this->createMock(KeycloakApiClient::class);
        $api->expects($this->once())
            ->method('fetchResourceUserIdStream')
            ->with($resource, $cache)
            ->willReturn($result);
        $sut = new UserCache($cache, $this->createStub(UserFactory::class), $api);
        $this->assertEquals($result, iterator_to_array($sut->getResourceUserIdStream($resource, false), false));
    }

    public function testItCanReturnTheResourceUserIdStreamWithTheOwner(): void
    {
        $owner = $this->createStub(User::class);
        $owner->method('getId')->willReturn('owner');
        $resource = $this->createMock(Resource::class);
        $resource->expects($this->once())
            ->method('getOwner')
            ->willReturn($owner);
        $resource->expects($this->once())
            ->method('getScopes')
            ->willReturn(new ResourceScopes('ownerscope'));
        $cache = $this->createStub(CacheAdapterInterface::class);
        $api = $this->createMock(KeycloakApiClient::class);
        $api->expects($this->once())
            ->method('fetchResourceUserIdStream')
            ->with($resource, $cache)
            ->willReturn([
                ['foo', new ResourceScopes('scope')],
                ['bar', new ResourceScopes('barscope', 'bazscope')]
            ]);
        $sut = new UserCache($cache, $this->createStub(UserFactory::class), $api);
        $this->assertEquals([
            ['owner', new ResourceScopes('ownerscope')],
            ['foo', new ResourceScopes('scope')],
            ['bar', new ResourceScopes('barscope', 'bazscope')]
        ],
            iterator_to_array($sut->getResourceUserIdStream($resource, true), false));
    }

    public function testItWorksAsEntityCache(): void
    {
        $userToCache = $this->createStub(User::class);
        $userToCache->method('jsonSerialize')->willReturn(['id' => 'foo']);
        $userFromCache = $this->createStub(User::class);
        $userFactory = $this->createMock(UserFactory::class);
        $userFactory->expects($this->once())->method('makeUserFromCacheData')->willReturn($userFromCache);
        $api = $this->createMock(KeycloakApiClient::class);
        $api->expects($this->once())->method('fetchUsersByIds')->with('foo')->willReturn([$userToCache]);
        $cache = new TestCacheAdapter();

        // First fetch -> get from api
        $fetchedUser = (new UserCache($cache, $userFactory, $api))->getOne('foo');
        $this->assertSame($userToCache, $fetchedUser);

        // Second fetch -> get from cache
        $cachedUser = (new UserCache($cache, $userFactory, $api))->getOne('foo');
        $this->assertSame($userFromCache, $cachedUser);
    }
}
