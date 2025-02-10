<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Keycloak\Query;

use Hawk\AuthClient\Groups\Value\GroupList;
use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Keycloak\Query\FetchGroupsQuery;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FetchGroupsQuery::class)]
#[CoversClass(KeycloakApiClient::class)]
class FetchGroupsQueryTest extends KeycloakQueryTestCase
{
    public function testItCanFetchGroups(): void
    {
        $this->client->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'admin/realms/{realm}/groups',
                [
                    'query' => [
                        'q' => '',
                        'max' => 1000
                    ]
                ]
            )->willReturn(
                $this->createStreamResponse(<<<JSON
[
	{
		"id": "8bb5d5fb-ac03-495e-ab80-41a39c6bc22b",
		"name": "group-added",
		"path": "/group-added",
		"subGroupCount": 1,
		"subGroups": [
			{
				"id": "d7392765-3f86-4b4b-b24c-51ed69ca3d9a",
				"name": "g1",
				"path": "/group-added/g1",
				"parentId": "8bb5d5fb-ac03-495e-ab80-41a39c6bc22b",
				"subGroupCount": 2,
				"subGroups": [
					{
						"id": "efb9ab68-af4c-46d6-b2e8-a4e0021b1af5",
						"name": "g23",
						"path": "/group-added/g1/g23",
						"parentId": "d7392765-3f86-4b4b-b24c-51ed69ca3d9a",
						"subGroupCount": 0,
						"subGroups": [],
						"access": {
							"view": true,
							"viewMembers": true,
							"manageMembers": false,
							"manage": false,
							"manageMembership": false
						}
					},
					{
						"id": "5e418efb-5da9-4f3a-8eba-15b22a87410f",
						"name": "g2",
						"path": "/group-added/g1/g2",
						"parentId": "d7392765-3f86-4b4b-b24c-51ed69ca3d9a",
						"subGroupCount": 0,
						"subGroups": [],
						"access": {
							"view": true,
							"viewMembers": true,
							"manageMembers": false,
							"manage": false,
							"manageMembership": false
						}
					}
				],
				"access": {
					"view": true,
					"viewMembers": true,
					"manageMembers": false,
					"manage": false,
					"manageMembership": false
				}
			}
		],
		"access": {
			"view": true,
			"viewMembers": true,
			"manageMembers": false,
			"manage": false,
			"manageMembership": false
		}
	}
]
JSON
                )
            );

        $result = $this->api->fetchGroups();

        $this->assertInstanceOf(GroupList::class, $result);
        $this->assertCount(1, $result);

        $expectedNames = ['group-added', 'g1', 'g23', 'g2'];
        foreach ($result as $i => $group) {
            $this->assertEquals($expectedNames[$i], $group->getName());
        }
    }

}
