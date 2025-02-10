<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Keycloak\Query;

use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Keycloak\Query\FetchResourceByNameQuery;
use Hawk\AuthClient\Resources\Value\Resource;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FetchResourceByNameQuery::class)]
#[CoversClass(KeycloakApiClient::class)]
class FetchResourceByNameQueryTest extends KeycloakQueryTestCase
{
    protected const string RESPONSE = '
[
	{
		"name": "resource-1",
		"type": "custom:type",
		"owner": {
			"id": "643fd89d-b519-413f-a345-2abc43a7cc05",
			"name": "hawk"
		},
		"ownerManagedAccess": false,
		"displayName": "Res1",
		"attributes": {},
		"_id": "a3735ddf-eb21-4ca5-8e2a-e510ecbf0d8d",
		"uris": [
			"/resource/123"
		],
		"icon_uri": ""
	}
]
';

    public function testItCanFetchAResource(): void
    {
        $resource = $this->createStub(Resource::class);
        $this->resourceFactory->expects($this->once())
            ->method('makeResourceFromKeycloakData')
            ->with(json_decode(self::RESPONSE, true)[0])
            ->willReturn($resource);
        $this->client->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'realms/{realm}/authz/protection/resource_set',
                [
                    'query' => [
                        'name' => 'resource-1',
                        'exactName' => 'true',
                        'deep' => 'true'
                    ]
                ]
            )->willReturn($this->createStreamResponse(self::RESPONSE));
        $this->assertEquals($resource, $this->api->fetchResourceByName('resource-1'));
    }

    public function testItReturnsNulIfResourceWasNotFound(): void
    {
        $this->resourceFactory->expects($this->never())
            ->method('makeResourceFromKeycloakData');

        $this->client->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'realms/{realm}/authz/protection/resource_set',
                [
                    'query' => [
                        'name' => 'resource-1',
                        'exactName' => 'true',
                        'deep' => 'true'
                    ]
                ]
            )->willReturn($this->createStreamResponse('[]'));

        $this->assertNull($this->api->fetchResourceByName('resource-1'));
    }

}
