<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Keycloak\Query;

use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Keycloak\Query\FetchResourcesByIdsQuery;
use Hawk\AuthClient\Resources\Value\Resource;
use Hawk\AuthClient\Util\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FetchResourcesByIdsQuery::class)]
#[CoversClass(KeycloakApiClient::class)]
class FetchResourcesByIdsQueryTest extends KeycloakQueryTestCase
{
    public const string RESPONSE = '
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
	},
	{
		"name": "uma-resource-3",
		"owner": {
			"id": "4fbd9b45-3852-4f2c-ac23-d5b6f02299f8",
			"name": "afrank"
		},
		"ownerManagedAccess": true,
		"attributes": {},
		"_id": "14407d98-bc56-4189-b20c-a4312a122890",
		"uris": [],
		"scopes": [
			{
				"id": "5779c19f-27c0-49ff-9d6f-2841a7a8f831",
				"name": "post-updates"
			},
			{
				"id": "57054a6c-aa34-479a-988f-58a929382dda",
				"name": "read-public"
			}
		]
	}
]';

    public function testItCanFetchObjects(): void
    {
        $this->client->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'realms/{realm}/hawk/resources',
                [
                    'query' => [
                        'max' => 2,
                        'ids' => 'a3735ddf-eb21-4ca5-8e2a-e510ecbf0d8d,14407d98-bc56-4189-b20c-a4312a122890'
                    ]
                ]
            )
            ->willReturn($this->createStreamResponse(self::RESPONSE));

        $resourceId1 = new Uuid('a3735ddf-eb21-4ca5-8e2a-e510ecbf0d8d');
        $resource1 = $this->createStub(Resource::class);
        $resource1->method('getId')->willReturn($resourceId1);
        $resourceId2 = new Uuid('14407d98-bc56-4189-b20c-a4312a122890');
        $resource2 = $this->createStub(Resource::class);
        $resource2->method('getId')->willReturn($resourceId2);

        $this->resourceFactory->method('makeResourceFromKeycloakData')->willReturnMap(
            [
                [json_decode(self::RESPONSE, true)[0], $resource1],
                [json_decode(self::RESPONSE, true)[1], $resource2]
            ]
        );

        $groups = [];
        foreach ($this->api->fetchResourcesByIds($resourceId1, $resourceId2) as $k => $resource) {
            $groups[(string)$k] = $resource;
        }

        $this->assertCount(2, $groups);

        $this->assertSame($resource1, $groups['a3735ddf-eb21-4ca5-8e2a-e510ecbf0d8d']);
        $this->assertSame($resource2, $groups['14407d98-bc56-4189-b20c-a4312a122890']);
    }

}
