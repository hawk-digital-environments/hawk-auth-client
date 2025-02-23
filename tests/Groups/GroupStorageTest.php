<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Groups;


use Hawk\AuthClient\Cache\CacheAdapterInterface;
use Hawk\AuthClient\Groups\GroupStorage;
use Hawk\AuthClient\Groups\Value\Group;
use Hawk\AuthClient\Groups\Value\GroupList;
use Hawk\AuthClient\Groups\Value\GroupReference;
use Hawk\AuthClient\Groups\Value\GroupReferenceList;
use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Tests\TestUtils\DummyUuid;
use Hawk\AuthClient\Util\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GroupStorage::class)]
class GroupStorageTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new GroupStorage(
            $this->createStub(CacheAdapterInterface::class),
            $this->createStub(KeycloakApiClient::class)
        );
        $this->assertInstanceOf(GroupStorage::class, $sut);
    }

    public function testItCanGetOne(): void
    {
        $group = $this->createStub(Group::class);
        $groupList = new GroupList($group);

        // Lookup with group reference
        $reference = new GroupReference('foo');
        $sut = $this->createPartialMock(GroupStorage::class, ['getAllInRefList']);
        $sut->expects($this->once())
            ->method('getAllInRefList')
            ->with($this->callback(fn(GroupReferenceList $list) => iterator_to_array($list, false) === [$reference]))
            ->willReturn($groupList);
        $this->assertSame($group, $sut->getOne($reference));

        // Lookup with group id
        $sut = $this->createPartialMock(GroupStorage::class, ['getAllInRefList']);
        $sut->expects($this->once())
            ->method('getAllInRefList')
            ->with($this->callback(fn(GroupReferenceList $list) => (string)iterator_to_array($list, false)[0] === 'foo'))
            ->willReturn($groupList);
        $this->assertSame($group, $sut->getOne('foo'));

        // Lookup with not existing id
        $sut = $this->createPartialMock(GroupStorage::class, ['getAllInRefList']);
        $sut->expects($this->once())
            ->method('getAllInRefList')
            ->with($this->callback(fn(GroupReferenceList $list) => (string)iterator_to_array($list, false)[0] === 'foo'))
            ->willReturn(new GroupList());
        $this->assertNull($sut->getOne('foo'));
    }

    public function testItCanGetAllInRefList(): void
    {
        $group1Id = new DummyUuid(1);
        $group1 = new Group($group1Id, 'name1', '/name1', new GroupList());
        $group2 = new Group(new DummyUuid(2), 'name2', '/name2', new GroupList());
        $group3 = new Group(new DummyUuid(3), 'name3', '/name3', new GroupList());

        $cache = $this->createStub(CacheAdapterInterface::class);
        $cache->method('remember')->willReturn(new GroupList($group1, $group2, $group3));

        $reference1 = new GroupReference((string)$group1Id);
        $reference2 = new GroupReference('name2');
        $reference3 = new GroupReference('/name3');

        $sut = new GroupStorage($cache, $this->createStub(KeycloakApiClient::class));
        $this->assertEquals(
            new GroupList($group1, $group2, $group3),
            $sut->getAllInRefList(new GroupReferenceList($reference1, $reference2, $reference3))
        );

        $sut = new GroupStorage($cache, $this->createStub(KeycloakApiClient::class));
        $this->assertEquals(
            new GroupList($group1, $group2),
            $sut->getAllInRefList(new GroupReferenceList($reference1, $reference2))
        );

        $sut = new GroupStorage($cache, $this->createStub(KeycloakApiClient::class));
        $this->assertEquals(
            new GroupList($group1, $group3),
            $sut->getAllInRefList(new GroupReferenceList($reference1, $reference3, new GroupReference('not-existing')))
        );
    }

    public function testItCanGetAll(): void
    {
        $group1 = new Group(new DummyUuid(1), 'name1', '/name1', new GroupList());
        $group2 = new Group(new DummyUuid(2), 'name2', '/name2', new GroupList());
        $group3 = new Group(new DummyUuid(3), 'name3', '/name3', new GroupList());

        $cache = $this->createStub(CacheAdapterInterface::class);
        $cache->method('remember')->willReturn(new GroupList($group1, $group2, $group3));

        $sut = new GroupStorage($cache, $this->createStub(KeycloakApiClient::class));
        $this->assertEquals(new GroupList($group1, $group2, $group3), $sut->getAll());
    }

    public function testItDoesCacheCorrectly(): void
    {
        $groupId = new DummyUuid(1);
        $apiGroupList = new GroupList(
            new Group(new Uuid($groupId), 'name1', '/name1', new GroupList()),
        );
        $api = $this->createMock(KeycloakApiClient::class);
        $api->expects($this->once())->method('fetchGroups')->willReturn($apiGroupList);

        $cache = $this->createMock(CacheAdapterInterface::class);
        $cache->expects($this->once())
            ->method('remember')
            ->willReturnCallback(function (
                string   $key,
                callable $valueGenerator,
                callable $valueToCache,
                callable $cacheToValue
            ) use ($apiGroupList, $groupId) {
                $this->assertEquals('keycloak.groups', $key);
                $this->assertSame($apiGroupList, $valueGenerator());
                $expectedJson = '[{"id":"' . $groupId . '","name":"name1","path":"\/name1","children":[]}]';
                $this->assertJsonStringEqualsJsonString(
                    $expectedJson,
                    $valueToCache($apiGroupList)
                );
                $this->assertEquals($apiGroupList, $cacheToValue($expectedJson));
                return $apiGroupList;
            });

        $sut = new GroupStorage($cache, $api);
        $sut->getAll();
    }

    public function testItCanFlushResolvedEntities(): void
    {
        $cache = $this->createMock(CacheAdapterInterface::class);
        $cache->expects($this->exactly(2))->method('remember')->willReturn(new GroupList());

        $sut = new GroupStorage($cache, $this->createStub(KeycloakApiClient::class));

        // Everything should be cached
        $sut->getOne('group-1');
        $sut->getOne('group-1');
        $sut->getOne('group-1');

        // Flush cache and fetch again
        $sut->flushResolved();
        $sut->getOne('group-1');
        $sut->getOne('group-1');
        $sut->getOne('group-1');
    }
}
