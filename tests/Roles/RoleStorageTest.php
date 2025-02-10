<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Roles;


use Hawk\AuthClient\Cache\CacheAdapterInterface;
use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Roles\RoleStorage;
use Hawk\AuthClient\Roles\Value\Role;
use Hawk\AuthClient\Roles\Value\RoleList;
use Hawk\AuthClient\Roles\Value\RoleReference;
use Hawk\AuthClient\Roles\Value\RoleReferenceList;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RoleStorage::class)]
class RoleStorageTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new RoleStorage(
            $this->createStub(CacheAdapterInterface::class),
            $this->createStub(KeycloakApiClient::class)
        );
        $this->assertInstanceOf(RoleStorage::class, $sut);
    }

    public function testItCanGetOne(): void
    {
        $role = $this->createStub(Role::class);
        $roleList = new RoleList($role);

        // Lookup with role reference
        $reference = new RoleReference('foo');
        $sut = $this->createPartialMock(RoleStorage::class, ['getAllInRefList']);
        $sut->expects($this->once())
            ->method('getAllInRefList')
            ->with($this->callback(fn(RoleReferenceList $list) => iterator_to_array($list, false) === [$reference]))
            ->willReturn($roleList);
        $this->assertSame($role, $sut->getOne($reference));

        // Lookup with role id
        $sut = $this->createPartialMock(RoleStorage::class, ['getAllInRefList']);
        $sut->expects($this->once())
            ->method('getAllInRefList')
            ->with($this->callback(fn(RoleReferenceList $list) => (string)iterator_to_array($list, false)[0] === 'foo'))
            ->willReturn($roleList);
        $this->assertSame($role, $sut->getOne('foo'));

        // Lookup with not existing id
        $sut = $this->createPartialMock(RoleStorage::class, ['getAllInRefList']);
        $sut->expects($this->once())
            ->method('getAllInRefList')
            ->with($this->callback(fn(RoleReferenceList $list) => (string)iterator_to_array($list, false)[0] === 'foo'))
            ->willReturn(new RoleList());
        $this->assertNull($sut->getOne('foo'));
    }

    public function testItCanGetAllInRefList(): void
    {
        $role1 = new Role('f47ac10b-58cc-4372-a567-0e02b2c3d001', 'foo', false, 'desc', []);
        $role2 = new Role('f47ac10b-58cc-4372-a567-0e02b2c3d002', 'bar', false, 'desc', []);
        $role3 = new Role('f47ac10b-58cc-4372-a567-0e02b2c3d003', 'baz', false, 'desc', []);

        $cache = $this->createStub(CacheAdapterInterface::class);
        $cache->method('remember')->willReturn(new RoleList($role1, $role2, $role3));

        $reference1 = new RoleReference('foo');
        $reference2 = new RoleReference('f47ac10b-58cc-4372-a567-0e02b2c3d002');
        $reference3 = new RoleReference('baz');

        $sut = new RoleStorage($cache, $this->createStub(KeycloakApiClient::class));
        $this->assertEquals(
            new RoleList($role1, $role2, $role3),
            $sut->getAllInRefList(new RoleReferenceList($reference1, $reference2, $reference3))
        );

        $sut = new RoleStorage($cache, $this->createStub(KeycloakApiClient::class));
        $this->assertEquals(
            new RoleList($role1, $role2),
            $sut->getAllInRefList(new RoleReferenceList($reference1, $reference2))
        );

        $sut = new RoleStorage($cache, $this->createStub(KeycloakApiClient::class));
        $this->assertEquals(
            new RoleList($role1, $role2, $role3),
            $sut->getAllInRefList(new RoleReferenceList($reference1, $reference2, $reference3, new RoleReference('qux')))
        );
    }

    public function testItCanGetAll(): void
    {
        $role1 = new Role('f47ac10b-58cc-4372-a567-0e02b2c3d001', 'foo', false, 'desc', []);
        $role2 = new Role('f47ac10b-58cc-4372-a567-0e02b2c3d002', 'bar', false, 'desc', []);
        $role3 = new Role('f47ac10b-58cc-4372-a567-0e02b2c3d003', 'baz', false, 'desc', []);

        $cache = $this->createStub(CacheAdapterInterface::class);
        $cache->method('remember')->willReturn(new RoleList($role1, $role2, $role3));

        $sut = new RoleStorage($cache, $this->createStub(KeycloakApiClient::class));
        $this->assertEquals(
            new RoleList($role1, $role2, $role3),
            $sut->getAll()
        );
    }

    public function testItDoesCacheCorrectly(): void
    {
        $apiRoleList = new RoleList(new Role('f47ac10b-58cc-4372-a567-0e02b2c3d001', 'foo', false, 'desc', []));
        $api = $this->createMock(KeycloakApiClient::class);
        $api->expects($this->once())->method('fetchRoles')->willReturn($apiRoleList);

        $cache = $this->createMock(CacheAdapterInterface::class);
        $cache->expects($this->once())
            ->method('remember')
            ->willReturnCallback(function (
                string   $key,
                callable $valueGenerator,
                callable $valueToCache,
                callable $cacheToValue
            ) use ($apiRoleList) {
                $this->assertEquals('keycloak.roles', $key);
                $this->assertSame($apiRoleList, $valueGenerator());
                $expectedJson = '[{"id":"f47ac10b-58cc-4372-a567-0e02b2c3d001","name":"foo","isClientRole":false,"description":"desc","attributes":[]}]';
                $this->assertJsonStringEqualsJsonString(
                    $expectedJson,
                    $valueToCache($apiRoleList)
                );
                $this->assertEquals($apiRoleList, $cacheToValue($expectedJson));
                return $apiRoleList;
            });

        $sut = new RoleStorage($cache, $api);
        $sut->getAll();
    }

    public function testItCanFlushResolvedEntities(): void
    {
        $cache = $this->createMock(CacheAdapterInterface::class);
        $cache->expects($this->exactly(2))->method('remember')->willReturn(new RoleList());

        $sut = new RoleStorage($cache, $this->createStub(KeycloakApiClient::class));

        // Everything should be cached
        $sut->getOne('role-1');
        $sut->getOne('role-1');
        $sut->getOne('role-1');

        // Flush cache and fetch again
        $sut->flushResolved();
        $sut->getOne('role-1');
        $sut->getOne('role-1');
        $sut->getOne('role-1');
    }
}
