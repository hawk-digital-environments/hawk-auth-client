<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Keycloak\Query;

use Hawk\AuthClient\Cache\CacheAdapterInterface;
use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Keycloak\Query\FetchResourceUserIdStreamQuery;
use Hawk\AuthClient\Resources\Value\Resource;
use Hawk\AuthClient\Resources\Value\ResourceScopes;
use Hawk\AuthClient\Tests\TestUtils\DummyUuid;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FetchResourceUserIdStreamQuery::class)]
#[CoversClass(KeycloakApiClient::class)]
class FetchResourceUserIdStreamQueryTest extends KeycloakQueryTestCase
{
    protected const string RESPONSE = '
[
	{
		"id": "3cb3fda0-8580-43e1-a6cf-20e0ef07c85a",
		"scopes": [
			"post-updates",
			"read-public"
		]
	},
	{
		"id": "3cb3fda0-8580-43e1-a6cf-20e0ef07c123",
		"scopes": [
			"post-updates"
		]
	}
]
';

    public function testItFetchesResourceUsersAndScopes(): void
    {
        $resourceId = new DummyUuid();

        $this->client->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'realms/{realm}/hawk/resources/' . $resourceId . '/users',
                [
                    'query' => [
                        'first' => 0,
                        'max' => 101
                    ]
                ]
            )
            ->willReturn($this->createStreamResponse(self::RESPONSE));

        $resource = $this->createStub(Resource::class);
        $resource->method('getId')->willReturn($resourceId);

        $cache = $this->createStub(CacheAdapterInterface::class);
        $cache->method('remember')->willReturnCallback(fn($key, $callback) => $callback());

        $result = iterator_to_array($this->api->fetchResourceUserIdStream($resource, $cache));

        $this->assertCount(2, $result);

        $this->assertEquals('3cb3fda0-8580-43e1-a6cf-20e0ef07c85a', (string)$result[0][0]);
        $this->assertEquals(new ResourceScopes('post-updates', 'read-public'), $result[0][1]);
        $this->assertEquals('3cb3fda0-8580-43e1-a6cf-20e0ef07c123', (string)$result[1][0]);
        $this->assertEquals(new ResourceScopes('post-updates'), $result[1][1]);
    }

}
