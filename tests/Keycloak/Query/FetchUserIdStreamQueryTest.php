<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Keycloak\Query;


use Hawk\AuthClient\Cache\CacheAdapterInterface;
use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Keycloak\Query\FetchUserIdStreamQuery;
use Hawk\AuthClient\Users\Value\UserConstraints;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(FetchUserIdStreamQuery::class)]
#[CoversClass(KeycloakApiClient::class)]
class FetchUserIdStreamQueryTest extends KeycloakQueryTestCase
{
    public static function provideTestItDoesFetchUserListData(): iterable
    {
        yield 'no constraints' => [
            null,
            [
                'idsOnly' => true
            ],
        ];
        yield 'id constraints' => [
            (new UserConstraints())
                ->withIds('user-id-1', 'user-id-2', 'user-id-3')
                // Those should be automatically disabled
                ->withSearch('search')
                ->withAttribute('attribute', 'value'),
            [
                'ids' => 'user-id-1,user-id-2,user-id-3',
                'idsOnly' => 'true'
            ],
        ];
        yield 'id constraints with allowed filters' => [
            (new UserConstraints())
                ->withIds('user-id-1', 'user-id-2', 'user-id-3')
                ->withOnlyOnline(),
            [
                'ids' => 'user-id-1,user-id-2,user-id-3',
                'onlineOnly' => 'true',
                'idsOnly' => 'true'
            ],
        ];
        yield 'all non id constraints' => [
            (new UserConstraints())
                ->withSearch('search')
                ->withAttribute('attribute', 'value')
                ->withAttribute('key', 'value')
                ->withOnlyOnline(),
            [
                'search' => 'search',
                'attributes' => 'attribute:value,key:value',
                'onlineOnly' => 'true',
                'idsOnly' => 'true'
            ],
        ];
    }

    #[DataProvider('provideTestItDoesFetchUserListData')]
    public function testItDoesFetchUserList(UserConstraints|null $constraints, array $expectedQuery): void
    {
        $response = $this->createStreamResponse('["user-id-1", "user-id-2", "user-id-3"]');
        $expectedResult = ['user-id-1', 'user-id-2', 'user-id-3'];
        $cache = $this->createMock(CacheAdapterInterface::class);
        $cache->expects($this->once())
            ->method('remember')
            ->willReturnCallback(fn(string $key, callable $callback) => $callback());#
        $this->client->expects($this->once())
            ->method('request')
            ->with('GET', 'realms/{realm}/hawk/users', ['query' => array_merge($expectedQuery, ['first' => 0, 'max' => 101])])
            ->willReturn($response);
        $result = $this->api->fetchUserIdStream($constraints, $cache);
        $this->assertEquals($expectedResult, iterator_to_array($result));
    }

}
