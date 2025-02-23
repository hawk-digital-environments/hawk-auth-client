<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Keycloak\Query;

use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Keycloak\Query\FetchProfileDataQuery;
use Hawk\AuthClient\Profiles\Value\UserProfile;
use Hawk\AuthClient\Tests\TestUtils\DummyUuid;
use Hawk\AuthClient\Users\Value\User;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FetchProfileDataQuery::class)]
#[CoversClass(KeycloakApiClient::class)]
class FetchProfileDataQueryTest extends KeycloakQueryTestCase
{
    protected const string RESPONSE = '
{
	"id": "83bbff9e-1b5a-428b-a520-4ea7c57fe744",
	"username": "akolb",
	"firstName": "Angelina",
	"lastName": "Kolb",
	"email": "angelina.kolb@email.com",
	"emailVerified": true,
	"userProfileMetadata": {
		"attributes": [
			{
				"name": "username",
				"displayName": "${username}",
				"required": true,
				"readOnly": true,
				"validators": {
					"username-prohibited-characters": {
						"ignore.empty.value": true
					},
					"multivalued": {
						"max": "1"
					},
					"length": {
						"max": 255,
						"ignore.empty.value": true,
						"min": 3
					},
					"up-username-not-idn-homograph": {
						"ignore.empty.value": true
					}
				},
				"multivalued": false
			},
			{
				"name": "email",
				"displayName": "${email}",
				"required": false,
				"readOnly": false,
				"validators": {
					"multivalued": {
						"max": "1"
					},
					"length": {
						"max": 255,
						"ignore.empty.value": true
					},
					"email": {
						"ignore.empty.value": true
					}
				},
				"multivalued": false
			},
			{
				"name": "firstName",
				"displayName": "${firstName}",
				"required": false,
				"readOnly": false,
				"validators": {
					"person-name-prohibited-characters": {
						"ignore.empty.value": true
					},
					"multivalued": {
						"max": "1"
					},
					"length": {
						"max": 255,
						"ignore.empty.value": true
					}
				},
				"multivalued": false
			},
			{
				"name": "lastName",
				"displayName": "${lastName}",
				"required": false,
				"readOnly": false,
				"validators": {
					"person-name-prohibited-characters": {
						"ignore.empty.value": true
					},
					"multivalued": {
						"max": "1"
					},
					"length": {
						"max": 255,
						"ignore.empty.value": true
					}
				},
				"multivalued": false
			}
		],
		"groups": [
			{
				"name": "user-metadata",
				"displayHeader": "User metadata",
				"displayDescription": "Attributes, which refer to user metadata"
			}
		]
	},
	"createdTimestamp": 1738678392793,
	"enabled": true,
	"totp": false,
	"disableableCredentialTypes": [],
	"requiredActions": [],
	"notBefore": 0,
	"access": {
		"manageGroupMembership": false,
		"view": true,
		"mapRoles": false,
		"impersonate": false,
		"manage": false
	}
} 
';

    public function testItCanFetchAUserProfile(): void
    {
        $userId = new DummyUuid();
        $user = $this->createStub(User::class);
        $user->method('getId')->willReturn($userId);
        $user->method('getUsername')->willReturn('username');

        $this->client->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'realms/{realm}/hawk/profile/' . $userId,
                [
                    'query' => [
                        'mode' => 'user'
                    ]
                ]
            )->willReturn(
                $this->createStreamResponse(self::RESPONSE)
            );

        $profile = $this->api->fetchUserProfile($user, false);

        $this->assertEquals('Angelina', $profile->getFirstName());
        $this->assertEquals('Kolb', $profile->getLastName());
        $this->assertEquals('angelina.kolb@email.com', $profile->getEmail());
        $this->assertTrue($profile->getAttribute(UserProfile::ATTRIBUTE_EMAIL_VERIFIED, null, false));
        $this->assertEquals([
            'id' => '83bbff9e-1b5a-428b-a520-4ea7c57fe744',
            'username' => 'akolb',
            'emailVerified' => true,
            'createdTimestamp' => 1738678392793,
            'enabled' => true,
            'totp' => false,
            'disableableCredentialTypes' => [],
            'requiredActions' => [],
            'notBefore' => 0,
            'access' => [
                'manageGroupMembership' => false,
                'view' => true,
                'mapRoles' => false,
                'impersonate' => false,
                'manage' => false
            ]
        ], $profile->getAdditionalData());
    }

    public function testItCanRequestUserProfileAsAdmin(): void
    {
        $userId = new DummyUuid();
        $user = $this->createStub(User::class);
        $user->method('getId')->willReturn($userId);
        $user->method('getUsername')->willReturn('username');

        $this->client->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'realms/{realm}/hawk/profile/' . $userId,
                [
                    'query' => [
                        'mode' => 'admin'
                    ]
                ]
            )->willReturn(
                $this->createStreamResponse(self::RESPONSE)
            );

        $this->api->fetchUserProfile($user, true);
    }
}
