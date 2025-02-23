<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Keycloak\Query;

use Hawk\AuthClient\Cache\CacheAdapterInterface;
use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Keycloak\Query\FetchGroupMemberIdStreamQuery;
use Hawk\AuthClient\Tests\TestUtils\DummyUuid;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FetchGroupMemberIdStreamQuery::class)]
#[CoversClass(KeycloakApiClient::class)]
class FetchGroupMemberIdsStreamQueryTest extends KeycloakQueryTestCase
{
    public function testItRequestsMemberIds(): void
    {
        $groupId = new DummyUuid();
        $this->client->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'admin/realms/{realm}/groups/' . $groupId . '/members',
                [
                    'query' => [
                        'briefRepresentation' => 'true',
                        'first' => 0,
                        'max' => 101
                    ]
                ]
            )->willReturn(
                $this->createStreamResponse(<<<JSON
[
	{
		"id": "40c3b030-4bbf-4764-85a5-4cd1f01f6be0",
		"username": "akern",
		"firstName": "Adrian",
		"lastName": "Kern",
		"email": "adrian.kern@email.com",
		"emailVerified": true,
		"createdTimestamp": 1738678383763,
		"enabled": true,
		"totp": false,
		"disableableCredentialTypes": [],
		"requiredActions": [],
		"notBefore": 0
	},
	{
		"id": "83bbff9e-1b5a-428b-a520-4ea7c57fe744",
		"username": "akolb",
		"firstName": "Angelina",
		"lastName": "Kolb",
		"email": "angelina.kolb@email.com",
		"emailVerified": true,
		"createdTimestamp": 1738678392793,
		"enabled": true,
		"totp": false,
		"disableableCredentialTypes": [],
		"requiredActions": [],
		"notBefore": 0
	},
	{
		"id": "949a6560-9fe4-4e6f-a6b2-ade3a38650fa",
		"username": "alenz",
		"firstName": "Anna-Sophie",
		"lastName": "Lenz",
		"email": "anna.lenz@email.com",
		"emailVerified": true,
		"createdTimestamp": 1738678389960,
		"enabled": true,
		"totp": false,
		"disableableCredentialTypes": [],
		"requiredActions": [],
		"notBefore": 0
	}
]
JSON
                )
            );

        $cache = $this->createMock(CacheAdapterInterface::class);
        $cache->expects($this->once())->method('remember')->willReturnCallback(fn($_, $valueGenerator) => $valueGenerator());

        $ids = $this->api->fetchGroupMemberIdStream($groupId, $cache);
        $this->assertEquals([
            '40c3b030-4bbf-4764-85a5-4cd1f01f6be0',
            '83bbff9e-1b5a-428b-a520-4ea7c57fe744',
            '949a6560-9fe4-4e6f-a6b2-ade3a38650fa'
        ], array_map('strval', iterator_to_array($ids)));
    }

}
