<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Keycloak\Query;


use Hawk\AuthClient\Cache\CacheAdapterInterface;
use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Keycloak\Query\FetchResourceIdStreamQuery;
use Hawk\AuthClient\Resources\Value\ResourceConstraints;
use Hawk\AuthClient\Tests\TestUtils\DummyUuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(FetchResourceIdStreamQuery::class)]
#[CoversClass(KeycloakApiClient::class)]
class FetchResourceIdStreamQueryTest extends KeycloakQueryTestCase
{
    public static function provideTestItDoesFetchResourceListData(): iterable
    {
        yield 'no constraints' => [
            null,
            [
                'idsOnly' => true
            ],
        ];
        yield 'ids constraints' => [
            (new ResourceConstraints())
                ->withIds(new DummyUuid(1), new DummyUuid(2), new DummyUuid(3))
                // Those should be automatically disabled
                ->withName('resource-name')
                ->withUri('resource-uri')
                ->withType('resource-type'),
            [
                'ids' => implode(',', [new DummyUuid(1), new DummyUuid(2), new DummyUuid(3)]),
                'idsOnly' => 'true'
            ],
        ];
        yield 'id constraints with allowed filters' => [
            (new ResourceConstraints())
                ->withIds(new DummyUuid(1), new DummyUuid(2), new DummyUuid(3))
                ->withSharedWith('shared-with')
                ->withOwner('owner', true)
            ,
            [
                'ids' => implode(',', [new DummyUuid(1), new DummyUuid(2), new DummyUuid(3)]),
                'sharedWith' => 'shared-with',
                'owner' => 'owner',
                'sharedOnly' => 'true',
                'idsOnly' => 'true'
            ],
        ];
        yield 'all non id constraints' => [
            (new ResourceConstraints())
                ->withName('resource-name')
                ->withUri('resource-uri')
                ->withType('resource-type')
                ->withSharedWith('shared-with')
                ->withOwner('owner')
            ,
            [
                'name' => 'resource-name',
                'uri' => 'resource-uri',
                'type' => 'resource-type',
                'sharedWith' => 'shared-with',
                'owner' => 'owner',
                'idsOnly' => 'true'
            ],
        ];
        yield 'exact names' => [
            (new ResourceConstraints())
                ->withName('resource-name-1', true)
            ,
            [
                'name' => 'resource-name-1',
                'exactNames' => 'true',
                'idsOnly' => 'true'
            ],
        ];
    }

    #[DataProvider('provideTestItDoesFetchResourceListData')]
    public function testItDoesFetchResourceList(ResourceConstraints|null $constraints, array $expectedQuery): void
    {
        $expectedResult = [new DummyUuid(1), new DummyUuid(2), new DummyUuid(3)];
        $response = $this->createStreamResponse(json_encode($expectedResult));
        $cache = $this->createMock(CacheAdapterInterface::class);
        $cache->expects($this->once())
            ->method('remember')
            ->willReturnCallback(fn(string $key, callable $callback) => $callback());
        $this->client->expects($this->once())
            ->method('request')
            ->with('GET', 'realms/{realm}/hawk/resources', ['query' => array_merge($expectedQuery, ['first' => 0, 'max' => 101])])
            ->willReturn($response);

        $result = $this->api->fetchResourceIdStream($constraints, $cache);
        $this->assertEquals($expectedResult, iterator_to_array($result));

    }

}
