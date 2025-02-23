<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Keycloak\Query;

use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Keycloak\Query\FetchRolesQuery;
use Hawk\AuthClient\Roles\Value\Role;
use Hawk\AuthClient\Roles\Value\RoleList;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FetchRolesQuery::class)]
#[CoversClass(KeycloakApiClient::class)]
class FetchRolesQueryTest extends KeycloakQueryTestCase
{
    protected const string RESPONSE = '
[
	{
		"id": "6f526a25-1111-4b03-a24a-9617ad0a4c6d",
		"name": "realm-role",
		"description": "A demo role in scope of the whole realm",
		"composite": false,
		"clientRole": false,
		"containerId": "044d9087-09fd-415e-aca8-4e4daa0fe95d",
		"attributes": {
		    "key": "value"
        }
	},
	{
		"id": "9d86335f-1732-4bfd-9d0f-8852af0f7c11",
		"name": "client-role",
		"description": "A demo role that is only available in the hawk client",
		"composite": false,
		"clientRole": true,
		"containerId": "643fd89d-b519-413f-a345-2abc43a7cc05",
		"attributes": {}
	}
]
';

    public function testItCanFetchTheRolesFromKeycloak(): void
    {
        $this->client->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'realms/{realm}/hawk/roles',
                [
                    'query' => [
                        'q' => '',
                        'max' => 1000
                    ]
                ]
            )
            ->willReturn($this->createStreamResponse(self::RESPONSE));

        $roleList = $this->api->fetchRoles();
        $this->assertInstanceOf(RoleList::class, $roleList);
        $this->assertEquals(2, $roleList->count());

        /** @var Role[] $roleArray */
        $roleArray = iterator_to_array($roleList);

        $this->assertEquals('realm-role', $roleArray[0]->getName());
        $this->assertEquals('6f526a25-1111-4b03-a24a-9617ad0a4c6d', (string)$roleArray[0]->getId());
        $this->assertEquals('A demo role in scope of the whole realm', $roleArray[0]->getDescription());
        $this->assertEquals('value', $roleArray[0]->getAttribute('key'));
        $this->assertFalse($roleArray[0]->isClientRole());

        $this->assertEquals('client-role', $roleArray[1]->getName());
        $this->assertEquals('9d86335f-1732-4bfd-9d0f-8852af0f7c11', (string)$roleArray[1]->getId());
        $this->assertEquals('A demo role that is only available in the hawk client', $roleArray[1]->getDescription());
        $this->assertEmpty($roleArray[1]->getAttributes());
        $this->assertTrue($roleArray[1]->isClientRole());
    }

}
