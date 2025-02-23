<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Keycloak\Query;

use Hawk\AuthClient\Cache\CacheAdapterInterface;
use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Keycloak\Query\FetchRoleMembersIdStreamQuery;
use Hawk\AuthClient\Tests\TestUtils\DummyUuid;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FetchRoleMembersIdStreamQuery::class)]
#[CoversClass(KeycloakApiClient::class)]
class FetchRoleMembersIdStreamQueryTest extends KeycloakQueryTestCase
{
    public function testItCanFetchRoleMembers(): void
    {
        $roleId = new DummyUuid();
        $this->client->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'realms/{realm}/hawk/roles/' . $roleId . '/members',
                [
                    'query' => [
                        'first' => 0,
                        'max' => 101
                    ]
                ]
            )->willReturn(
                $this->createStreamResponse('["3cb3fda0-8580-43e1-a6cf-20e0ef07c85a","3cb3fda0-8580-43e1-a6cf-20e0ef07c123"]'));

        $cache = $this->createStub(CacheAdapterInterface::class);
        $cache->method('remember')->willReturnCallback(fn($key, $callback) => $callback());

        $result = iterator_to_array($this->api->fetchRoleMemberIdStream($roleId, $cache));

        $this->assertCount(2, $result);

        $this->assertSame('3cb3fda0-8580-43e1-a6cf-20e0ef07c85a', (string)$result[0]);
        $this->assertSame('3cb3fda0-8580-43e1-a6cf-20e0ef07c123', (string)$result[1]);
    }

}
