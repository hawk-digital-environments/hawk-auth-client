<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Users;


use Hawk\AuthClient\Cache\CacheAdapterInterface;
use Hawk\AuthClient\Groups\Value\Group;
use Hawk\AuthClient\Keycloak\ConnectionInfoStorage;
use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Keycloak\Value\ConnectionInfo;
use Hawk\AuthClient\Resources\Value\Resource;
use Hawk\AuthClient\Resources\Value\ResourceScopes;
use Hawk\AuthClient\Roles\Value\Role;
use Hawk\AuthClient\Tests\TestUtils\DummyUuid;
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
            $this->createStub(KeycloakApiClient::class),
            $this->createStub(ConnectionInfoStorage::class)
        );
        $this->assertInstanceOf(UserCache::class, $sut);
    }

    public function testItCanGetASingleUserByToken(): void
    {
        $id = new DummyUuid();
        $cache = $this->createMock(CacheAdapterInterface::class);
        $cache->expects($this->once())
            ->method('remember')
            ->willReturn($id);

        $sut = $this->createPartialMockWithConstructorArgs(UserCache::class, ['getOne'], [
            $cache,
            $this->createStub(UserFactory::class),
            $this->createStub(KeycloakApiClient::class),
            $this->createStub(ConnectionInfoStorage::class)
        ]);
        $sut->expects($this->once())
            ->method('getOne')
            ->with($id)
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
            $this->createStub(KeycloakApiClient::class),
            $this->createStub(ConnectionInfoStorage::class)
        ]);
        $sut->expects($this->never())->method('getOne');

        $this->assertNull($sut->getOneByToken(
            new AccessToken(['access_token' => 'foo']),
            fn() => $this->fail('Should not be called')
        ));
    }

    public function testItCachesTheAccessTokenToIdMappingCorrectly(): void
    {
        $testUuid = new DummyUuid();
        $userId = new DummyUuid(1);

        $cache = $this->createMock(CacheAdapterInterface::class);
        $cache->expects($this->once())
            ->method('remember')
            ->willReturnCallback(function (
                string   $key,
                callable $valueGenerator,
                         $_,
                         $_1,
                int      $ttl
            ) use ($testUuid, $userId) {
                $this->assertEquals('keycloak.user.by_token.' . hash('sha256', 'foo'), $key);
                $this->assertSame($testUuid, $valueGenerator());
                $this->assertEquals(60 * 15, $ttl);
                return $userId;
            });

        $sut = $this->createPartialMockWithConstructorArgs(UserCache::class, ['getOne'], [
            $cache,
            $this->createStub(UserFactory::class),
            $this->createStub(KeycloakApiClient::class),
            $this->createStub(ConnectionInfoStorage::class)
        ]);
        $sut->expects($this->once())
            ->method('getOne')
            ->with($userId)
            ->willReturn($this->createStub(User::class));

        $this->assertInstanceOf(User::class, $sut->getOneByToken(
            new AccessToken(['access_token' => 'foo']),
            function () use ($testUuid) {
                $user = $this->createStub(User::class);
                $user->method('getId')->willReturn($testUuid);
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
        $sut = new UserCache($cache, $this->createStub(UserFactory::class), $api, $this->createStub(ConnectionInfoStorage::class));
        $this->assertEquals([], iterator_to_array($sut->getUserIdStream(null), false));
    }

    public function testItCanReturnTheUserIdStreamWithConstraints(): void
    {
        $id1 = new DummyUuid(1);
        $id2 = new DummyUuid(2);
        $id3 = new DummyUuid(3);
        $constraints = $this->createStub(UserConstraints::class);
        $cache = $this->createStub(CacheAdapterInterface::class);
        $api = $this->createMock(KeycloakApiClient::class);
        $api->expects($this->once())
            ->method('fetchUserIdStream')
            ->with($constraints, $cache)
            ->willReturn([$id1, $id2, $id3]);
        $sut = new UserCache($cache, $this->createStub(UserFactory::class), $api, $this->createStub(ConnectionInfoStorage::class));
        $this->assertEquals([$id1, $id2, $id3], iterator_to_array($sut->getUserIdStream($constraints), false));
    }

    public function testItCanReturnTheGroupMemberIdStream(): void
    {
        $id1 = new DummyUuid(1);
        $id2 = new DummyUuid(2);
        $id3 = new DummyUuid(3);
        $group = $this->createStub(Group::class);
        $cache = $this->createStub(CacheAdapterInterface::class);
        $api = $this->createMock(KeycloakApiClient::class);
        $api->expects($this->once())
            ->method('fetchGroupMemberIdStream')
            ->with($group->getId(), $cache)
            ->willReturn([$id1, $id2, $id3]);
        $sut = new UserCache($cache, $this->createStub(UserFactory::class), $api, $this->createStub(ConnectionInfoStorage::class));
        $this->assertEquals([$id1, $id2, $id3], iterator_to_array($sut->getGroupMemberIdStream($group), false));
    }

    public function testItCanReturnTheRoleMemberStream(): void
    {
        $id1 = new DummyUuid(1);
        $id2 = new DummyUuid(2);
        $id3 = new DummyUuid(3);
        $role = $this->createStub(Role::class);
        $cache = $this->createStub(CacheAdapterInterface::class);
        $api = $this->createMock(KeycloakApiClient::class);
        $api->expects($this->once())
            ->method('fetchRoleMemberIdStream')
            ->with($role->getId(), $cache)
            ->willReturn([$id1, $id2, $id3]);
        $sut = new UserCache($cache, $this->createStub(UserFactory::class), $api, $this->createStub(ConnectionInfoStorage::class));
        $this->assertEquals([$id1, $id2, $id3], iterator_to_array($sut->getRoleMemberIdStream($role), false));
    }

    public function testItCanReturnTheResourceUserIdStreamWithoutTheOwner(): void
    {
        $id1 = new DummyUuid(1);
        $id2 = new DummyUuid(1);
        $result = [[$id1, ['scope']], [$id2, ['barscope', 'bazscope']]];
        $resource = $this->createMock(Resource::class);
        $resource->expects($this->never())->method('getOwner');
        $cache = $this->createStub(CacheAdapterInterface::class);
        $api = $this->createMock(KeycloakApiClient::class);
        $api->expects($this->once())
            ->method('fetchResourceUserIdStream')
            ->with($resource, $cache)
            ->willReturn($result);
        $sut = new UserCache($cache, $this->createStub(UserFactory::class), $api, $this->createStub(ConnectionInfoStorage::class));
        $this->assertEquals($result, iterator_to_array($sut->getResourceUserIdStream($resource, false), false));
    }

    public function testItCanReturnTheResourceUserIdStreamWithTheOwner(): void
    {
        $ownerId = new DummyUuid(1);
        $id1 = new DummyUuid(2);
        $id2 = new DummyUuid(3);
        $owner = $this->createStub(User::class);
        $owner->method('getId')->willReturn(new DummyUuid());
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
                [$id1, new ResourceScopes('scope')],
                [$id2, new ResourceScopes('barscope', 'bazscope')]
            ]);
        $sut = new UserCache($cache, $this->createStub(UserFactory::class), $api, $this->createStub(ConnectionInfoStorage::class));
        $this->assertEquals([
            [(string)$ownerId, new ResourceScopes('ownerscope')],
            [(string)$id1, new ResourceScopes('scope')],
            [(string)$id2, new ResourceScopes('barscope', 'bazscope')]
        ],
            iterator_to_array($sut->getResourceUserIdStream($resource, true), false));
    }

    public function testItWorksAsEntityCache(): void
    {
        $id = new DummyUuid();
        $userToCache = $this->createStub(User::class);
        $userToCache->method('jsonSerialize')->willReturn(['id' => (string)$id]);
        $userFromCache = $this->createStub(User::class);
        $userFactory = $this->createMock(UserFactory::class);
        $userFactory->expects($this->once())->method('makeUserFromCacheData')->willReturn($userFromCache);
        $api = $this->createMock(KeycloakApiClient::class);
        $api->expects($this->once())->method('fetchUsersByIds')->with($id)->willReturn([$userToCache]);
        $cache = new TestCacheAdapter();

        // First fetch -> get from api
        $fetchedUser = (new UserCache($cache, $userFactory, $api, $this->createStub(ConnectionInfoStorage::class)))
            ->getOne($id);
        $this->assertSame($userToCache, $fetchedUser);

        // Second fetch -> get from cache
        $cachedUser = (new UserCache($cache, $userFactory, $api, $this->createStub(ConnectionInfoStorage::class)))
            ->getOne($id);
        $this->assertSame($userFromCache, $cachedUser);
    }

    public function testItExplicitlyRemapsClientUuidsToServiceAccountUuidsForOne(): void
    {
        $clientUuid = new DummyUuid(1);
        $serviceAccountUuid = new DummyUuid(2);
        $connectionInfo = $this->createStub(ConnectionInfo::class);
        $connectionInfo->method('getClientUuid')->willReturn($clientUuid);
        $connectionInfo->method('getClientServiceAccountUuid')->willReturn($serviceAccountUuid);
        $connectionInfoStorage = $this->createStub(ConnectionInfoStorage::class);
        $connectionInfoStorage->method('getConnectionInfo')->willReturn($connectionInfo);
        $user = $this->createStub(User::class);

        $cache = $this->createStub(CacheAdapterInterface::class);
        $api = $this->createMock(KeycloakApiClient::class);
        $api->method('fetchUsersByIds')->with($serviceAccountUuid)->willReturn([$user]);

        $sut = new UserCache($cache, $this->createStub(UserFactory::class), $api, $connectionInfoStorage);

        $result = $sut->getOne($clientUuid);

        $this->assertSame($user, $result);
    }

    public function testItExplicitlyRemapsClientUuidsToServiceAccountUuidsForOneForList(): void
    {
        $userUuid1 = new DummyUuid(1);
        $userUuid2 = new DummyUuid(2);
        $clientUuid = new DummyUuid(3);
        $serviceAccountUuid = new DummyUuid(4);
        $connectionInfo = $this->createStub(ConnectionInfo::class);
        $connectionInfo->method('getClientUuid')->willReturn($clientUuid);
        $connectionInfo->method('getClientServiceAccountUuid')->willReturn($serviceAccountUuid);
        $connectionInfoStorage = $this->createStub(ConnectionInfoStorage::class);
        $connectionInfoStorage->method('getConnectionInfo')->willReturn($connectionInfo);
        $user1 = $this->createStub(User::class);
        $user1->method('getId')->willReturn($userUuid1);
        $user2 = $this->createStub(User::class);
        $user2->method('getId')->willReturn($userUuid2);
        $user3 = $this->createStub(User::class);
        $user3->method('getId')->willReturn($serviceAccountUuid);

        $cache = $this->createStub(CacheAdapterInterface::class);
        $api = $this->createMock(KeycloakApiClient::class);
        $api->method('fetchUsersByIds')->with($userUuid1, $userUuid2, $serviceAccountUuid)->willReturn((function () use ($user1, $user2, $user3) {
            yield $user1->getId() => $user1;
            yield $user2->getId() => $user2;
            yield $user3->getId() => $user3;
        })());

        $sut = new UserCache($cache, $this->createStub(UserFactory::class), $api, $connectionInfoStorage);

        $result = $sut->getAllByIds($userUuid1, $userUuid2, $clientUuid);

        $this->assertSame([
            $user1,
            $user2,
            $user3
        ], [...$result]);
    }
}
