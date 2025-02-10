<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Users;


use Hawk\AuthClient\Groups\Value\Group;
use Hawk\AuthClient\Groups\Value\GroupReferenceList;
use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Resources\Value\Resource;
use Hawk\AuthClient\Resources\Value\ResourceScopes;
use Hawk\AuthClient\Roles\Value\Role;
use Hawk\AuthClient\Roles\Value\RoleReferenceList;
use Hawk\AuthClient\Users\UserCache;
use Hawk\AuthClient\Users\UserStorage;
use Hawk\AuthClient\Users\Value\ResourceUser;
use Hawk\AuthClient\Users\Value\User;
use Hawk\AuthClient\Users\Value\UserClaims;
use Hawk\AuthClient\Users\Value\UserConstraints;
use Hawk\AuthClient\Users\Value\UserContext;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UserStorage::class)]
class UserStorageTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new UserStorage($this->createStub(KeycloakApiClient::class), $this->createStub(UserCache::class));
        $this->assertInstanceOf(UserStorage::class, $sut);
    }

    public function testItCanGetOne(): void
    {
        $user = $this->createStub(User::class);
        $cache = $this->createMock(UserCache::class);
        $cache->expects($this->once())->method('getOne')->with('foo')->willReturn($user);
        $sut = new UserStorage($this->createStub(KeycloakApiClient::class), $cache);
        $this->assertSame($user, $sut->getOne('foo'));
    }

    public function testItCanGetAll(): void
    {
        $user1 = $this->createStub(User::class);
        $user2 = $this->createStub(User::class);
        $user3 = $this->createStub(User::class);
        $constraints = $this->createStub(UserConstraints::class);
        $cache = $this->createMock(UserCache::class);
        $cache->expects($this->once())->method('getUserIdStream')->with($constraints)->willReturn(['foo', 'bar', 'baz']);
        $cache->expects($this->once())->method('getAllByIds')->with('foo', 'bar', 'baz')->willReturn([$user1, $user2, $user3]);

        $result = (new UserStorage($this->createStub(KeycloakApiClient::class), $cache))->getAll($constraints);
        $this->assertSame([$user1, $user2, $user3], iterator_to_array($result, false));
    }

    public function testItCanGetGroupMembers(): void
    {
        $user1 = $this->createStub(User::class);
        $user2 = $this->createStub(User::class);
        $user3 = $this->createStub(User::class);
        $group = $this->createStub(Group::class);
        $cache = $this->createMock(UserCache::class);
        $cache->expects($this->once())->method('getGroupMemberIdStream')->with($group)->willReturn(['foo', 'bar', 'baz']);
        $cache->expects($this->once())->method('getAllByIds')->with('foo', 'bar', 'baz')->willReturn([$user1, $user2, $user3]);
        $result = (new UserStorage($this->createStub(KeycloakApiClient::class), $cache))->getGroupMembers($group);
        $this->assertSame([$user1, $user2, $user3], iterator_to_array($result, false));
    }

    public function testItCanGetRoleMembers(): void
    {
        $user1 = $this->createStub(User::class);
        $user2 = $this->createStub(User::class);
        $user3 = $this->createStub(User::class);
        $role = $this->createStub(Role::class);
        $cache = $this->createMock(UserCache::class);
        $cache->expects($this->once())->method('getRoleMemberIdStream')->with($role)->willReturn(['foo', 'bar', 'baz']);
        $cache->expects($this->once())->method('getAllByIds')->with('foo', 'bar', 'baz')->willReturn([$user1, $user2, $user3]);
        $result = (new UserStorage($this->createStub(KeycloakApiClient::class), $cache))->getRoleMembers($role);
        $this->assertSame([$user1, $user2, $user3], iterator_to_array($result, false));
    }

    public function testItCanGetOneByToken(): void
    {
        $token = $this->createStub(AccessToken::class);
        $fallback = fn() => null;
        $user = $this->createStub(User::class);
        $cache = $this->createMock(UserCache::class);
        $cache->expects($this->once())->method('getOneByToken')->with($token, $fallback)->willReturn($user);
        $sut = new UserStorage($this->createStub(KeycloakApiClient::class), $cache);
        $this->assertSame($user, $sut->getOneByToken($token, $fallback));
    }

    public function testItCanGetResourceUsers(): void
    {
        $users = [];
        $userIdStream = [];
        foreach (range(1, 150) as $i) {
            $id = str_pad((string)$i, 3, '0', STR_PAD_LEFT);
            $uuid = '83335934-fc49-4c59-8199-de47c3d03' . $id;
            $user = new User(
                $uuid,
                'user-' . $id,
                $this->createStub(UserClaims::class),
                $this->createStub(RoleReferenceList::class),
                $this->createStub(GroupReferenceList::class),
                $this->createStub(UserContext::class)
            );
            $users[$uuid] = $user;
            if (random_int(1, 3) === 1) {
                $scopes = new ResourceScopes('read', 'write', 'delete');
            } elseif (random_int(1, 6) === 1) {
                $scopes = new ResourceScopes('read', 'write');
            } else {
                $scopes = new ResourceScopes('read');
            }
            $userIdStream[$uuid] = [$uuid, $scopes];
        }

        $resource = $this->createStub(Resource::class);
        $cache = $this->createMock(UserCache::class);
        $cache->expects($this->once())->method('getResourceUserIdStream')->with($resource, true)->willReturn(array_values($userIdStream));
        $cache->expects($this->atLeastOnce())->method('getAllByIds')->willReturnCallback(function (string ...$ids) use ($users) {
            return array_map(static fn($id) => $users[$id], $ids);
        });

        $result = (new UserStorage($this->createStub(KeycloakApiClient::class), $cache))->getResourceUsers($resource, true);
        foreach ($result as $user) {
            $this->assertInstanceOf(ResourceUser::class, $user);
            $this->assertEquals($users[$user->getId()]->getId(), $user->getId());
            $this->assertEquals($userIdStream[$user->getId()][0], $user->getId());
            $this->assertSame($userIdStream[$user->getId()][1], $user->getScopes());
        }
    }
}
