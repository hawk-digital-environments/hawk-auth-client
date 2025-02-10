<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Keycloak\Query;

use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Keycloak\Query\FetchUsersByIdsQuery;
use Hawk\AuthClient\Users\Value\User;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FetchUsersByIdsQuery::class)]
#[CoversClass(KeycloakApiClient::class)]
class FetchUsersByIdsQueryTest extends KeycloakQueryTestCase
{
    protected const string RESPONSE = '
[
    {
		"sub": "29301673-cdb5-4200-8590-211642973711",
		"email_verified": true,
		"name": "Anna-Lena Keller",
		"preferred_username": "akeller",
		"given_name": "Anna-Lena",
		"family_name": "Keller",
		"hawk": {
			"roles": {
				"client": {
					"account": [
						"manage-account",
						"manage-account-links",
						"view-profile"
					]
				},
				"realm": [
					"offline_access",
					"default-roles-dev-realm",
					"uma_authorization"
				]
			}
		},
		"email": "anna.keller@email.com"
	},
	{
		"sub": "40c3b030-4bbf-4764-85a5-4cd1f01f6be0",
		"email_verified": true,
		"name": "Adrian Kern",
		"preferred_username": "akern",
		"given_name": "Adrian",
		"family_name": "Kern",
		"hawk": {
			"roles": {
				"client": {
					"account": [
						"manage-account",
						"manage-account-links",
						"view-profile"
					]
				},
				"realm": [
					"offline_access",
					"default-roles-dev-realm",
					"uma_authorization"
				]
			},
			"groups": [
				"/Fakultät Bauen und Erhalten"
			]
		},
		"email": "adrian.kern@email.com"
	},
	{
		"sub": "83bbff9e-1b5a-428b-a520-4ea7c57fe744",
		"email_verified": true,
		"name": "Angelina Kolb",
		"preferred_username": "akolb",
		"given_name": "Angelina",
		"family_name": "Kolb",
		"hawk": {
			"roles": {
				"client": {
					"account": [
						"manage-account",
						"manage-account-links",
						"view-profile"
					]
				},
				"realm": [
					"realm-role",
					"offline_access",
					"default-roles-dev-realm",
					"uma_authorization"
				]
			},
			"groups": [
				"/Fakultät Bauen und Erhalten"
			]
		},
		"email": "angelina.kolb@email.com"
	}
]';

    public function testItCanFetchObjects(): void
    {
        $this->client->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'realms/{realm}/hawk/users',
                [
                    'query' => [
                        'max' => 3,
                        'ids' => '29301673-cdb5-4200-8590-211642973711,40c3b030-4bbf-4764-85a5-4cd1f01f6be0,83bbff9e-1b5a-428b-a520-4ea7c57fe744'
                    ]
                ]
            )->willReturn($this->createStreamResponse(self::RESPONSE));

        $user1 = $this->createStub(User::class);
        $user1->method('getId')->willReturn('29301673-cdb5-4200-8590-211642973711');
        $user2 = $this->createStub(User::class);
        $user2->method('getId')->willReturn('40c3b030-4bbf-4764-85a5-4cd1f01f6be0');
        $user3 = $this->createStub(User::class);
        $user3->method('getId')->willReturn('83bbff9e-1b5a-428b-a520-4ea7c57fe744');

        $this->userFactory->expects($this->exactly(3))
            ->method('makeUserFromKeycloakData')
            ->willReturnCallback(
                function ($data) use ($user1, $user2, $user3) {
                    if ($data['sub'] === '29301673-cdb5-4200-8590-211642973711') {
                        $this->assertEquals(json_decode(self::RESPONSE, true)[0], $data);
                        return $user1;
                    }
                    if ($data['sub'] === '40c3b030-4bbf-4764-85a5-4cd1f01f6be0') {
                        $this->assertEquals(json_decode(self::RESPONSE, true)[1], $data);
                        return $user2;
                    }
                    if ($data['sub'] === '83bbff9e-1b5a-428b-a520-4ea7c57fe744') {
                        $this->assertEquals(json_decode(self::RESPONSE, true)[2], $data);
                        return $user3;
                    }
                    $this->fail('Unexpected user data');
                }
            );

        $result = $this->api->fetchUsersByIds(
            '29301673-cdb5-4200-8590-211642973711',
            '40c3b030-4bbf-4764-85a5-4cd1f01f6be0',
            '83bbff9e-1b5a-428b-a520-4ea7c57fe744'
        );

        /** @var User[] $users */
        $users = iterator_to_array($result);

        $this->assertCount(3, $users);
        $this->assertArrayHasKey('29301673-cdb5-4200-8590-211642973711', $users);
        $this->assertSame($user1, $users['29301673-cdb5-4200-8590-211642973711']);
        $this->assertArrayHasKey('40c3b030-4bbf-4764-85a5-4cd1f01f6be0', $users);
        $this->assertSame($user2, $users['40c3b030-4bbf-4764-85a5-4cd1f01f6be0']);
        $this->assertArrayHasKey('83bbff9e-1b5a-428b-a520-4ea7c57fe744', $users);
        $this->assertSame($user3, $users['83bbff9e-1b5a-428b-a520-4ea7c57fe744']);
    }
}
