<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Keycloak\Query;

use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Keycloak\Query\FetchResourceScopesGrantedToUserQuery;
use Hawk\AuthClient\Resources\Value\Resource;
use Hawk\AuthClient\Resources\Value\ResourceScopes;
use Hawk\AuthClient\Tests\TestUtils\DummyUuid;
use Hawk\AuthClient\Users\Value\User;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FetchResourceScopesGrantedToUserQuery::class)]
#[CoversClass(KeycloakApiClient::class)]
class FetchResourceScopesGrantedToUserQueryTest extends KeycloakQueryTestCase
{
    public const string RESULT = '
{
	"results": [
		{
			"resource": {
				"name": "test-resource with scopes [read-public]",
				"_id": "a4419ae3-2461-41f1-b2b5-29fd21630f36"
			},
			"scopes": [
				{
					"id": "5b30d252-4ba1-44bb-9c0a-64455bc61d8f",
					"name": "read-public"
				}
			],
			"policies": [
				{
					"policy": {
						"id": "f5c2e10a-57f4-45e4-a906-33ea0ef8b913",
						"name": "74a4c4c3-b7b3-4416-b946-1e7332517869",
						"description": "Resource owner (ich@du.de) grants access to ich@du.de",
						"type": "uma",
						"resources": [
							"test-resource-martin"
						],
						"scopes": [
							"read-public"
						],
						"logic": "POSITIVE",
						"decisionStrategy": "UNANIMOUS",
						"config": {}
					},
					"status": "PERMIT",
					"associatedPolicies": [],
					"scopes": []
				}
			],
			"status": "PERMIT",
			"allowedScopes": [
				{
					"id": "5b30d252-4ba1-44bb-9c0a-64455bc61d8f",
					"name": "read-public"
				}
			]
		}
	],
	"entitlements": false,
	"status": "PERMIT",
	"rpt": {
		"exp": 1738616145,
		"iat": 1738615845,
		"jti": "2097725c-b911-46a5-bac4-5f65799b8657",
		"aud": "users",
		"sub": "ad4da651-dc26-4419-bb32-008f227e0970",
		"typ": "Bearer",
		"azp": "users",
		"sid": "e1a5ccab-049b-4cfd-9e49-80389512c320",
		"acr": "1",
		"allowed-origins": [
			"http://localhost"
		],
		"realm_access": {
			"roles": [
				"offline_access",
				"uma_authorization",
				"default-roles-users"
			]
		},
		"resource_access": {
			"account": {
				"roles": [
					"manage-account",
					"manage-account-links",
					"view-profile"
				]
			}
		},
		"authorization": {
			"permissions": [
				{
					"scopes": [
						"read-public"
					],
					"rsid": "a4419ae3-2461-41f1-b2b5-29fd21630f36",
					"rsname": "test-resource-martin"
				}
			]
		},
		"scope": "email profile",
		"email_verified": false,
		"name": "fa faz",
		"groups": [
			"offline_access",
			"uma_authorization",
			"default-roles-users"
		],
		"preferred_username": "faz",
		"given_name": "fa",
		"family_name": "faz",
		"email": "ich@du.de"
	}
}
';

    public function testItCanFetchResourceScopes(): void
    {
        $userId = new DummyUuid(1);
        $resourceId = new DummyUuid(2);
        $this->client->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'admin/realms/{realm}/clients/{clientUuid}/authz/resource-server/permission/evaluate',
                [
                    'json' => [
                        'roleIds' => [],
                        'userId' => (string)$userId,
                        'entitlements' => true,
                        'context' => [
                            'attributes' => (object)[]
                        ],
                        'resources' => [[
                            '_id' => (string)$resourceId
                        ]]
                    ]
                ]
            )
            ->willReturn($this->createStreamResponse(self::RESULT));

        $user = $this->createStub(User::class);
        $user->method('getId')->willReturn($userId);
        $resource = $this->createStub(Resource::class);
        $resource->method('getId')->willReturn($resourceId);

        $scopes = $this->api->fetchGrantedResourceScopesForUser($resource, $user);
        $this->assertInstanceOf(ResourceScopes::class, $scopes);
        $this->assertEquals(['read-public'], $scopes->jsonSerialize());
    }

    public function testItWillReturnNullIfNoScopesAreGranted(): void
    {
        $userId = new DummyUuid(1);
        $resourceId = new DummyUuid(2);
        $this->client->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'admin/realms/{realm}/clients/{clientUuid}/authz/resource-server/permission/evaluate',
                [
                    'json' => [
                        'roleIds' => [],
                        'userId' => (string)$userId,
                        'entitlements' => true,
                        'context' => [
                            'attributes' => (object)[]
                        ],
                        'resources' => [[
                            '_id' => (string)$resourceId
                        ]]
                    ]
                ]
            )
            ->willReturn($this->createStreamResponse('{"results": [], "entitlements": false}'));

        $user = $this->createStub(User::class);
        $user->method('getId')->willReturn($userId);
        $resource = $this->createStub(Resource::class);
        $resource->method('getId')->willReturn($resourceId);

        $scopes = $this->api->fetchGrantedResourceScopesForUser($resource, $user);

        $this->assertNull($scopes);
    }

}
