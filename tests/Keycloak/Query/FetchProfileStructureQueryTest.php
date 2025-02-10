<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Keycloak\Query;


use Hawk\AuthClient\Exception\FailedRequestDueToMissingPermissionsException;
use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Keycloak\Query\FetchProfileStructureQuery;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FetchProfileStructureQuery::class)]
#[CoversClass(KeycloakApiClient::class)]
#[CoversClass(FailedRequestDueToMissingPermissionsException::class)]
class FetchProfileStructureQueryTest extends KeycloakQueryTestCase
{
    protected const string RESPONSE = '
{
	"attributes": [
		{
			"name": "username",
			"displayName": "${username}",
			"validations": {
				"length": {
					"min": 3,
					"max": 255
				},
				"username-prohibited-characters": {},
				"up-username-not-idn-homograph": {}
			},
			"permissions": {
				"view": [
					"admin",
					"user"
				],
				"edit": [
					"admin",
					"user"
				]
			},
			"multivalued": false
		},
		{
			"name": "email",
			"displayName": "${email}",
			"validations": {
				"email": {},
				"length": {
					"max": 255
				}
			},
			"required": {
				"roles": [
					"user"
				]
			},
			"permissions": {
				"view": [
					"admin",
					"user"
				],
				"edit": [
					"admin",
					"user"
				]
			},
			"multivalued": false
		},
		{
			"name": "firstName",
			"displayName": "${firstName}",
			"validations": {
				"length": {
					"max": 255
				},
				"person-name-prohibited-characters": {}
			},
			"required": {
				"roles": [
					"user"
				]
			},
			"permissions": {
				"view": [
					"admin",
					"user"
				],
				"edit": [
					"admin",
					"user"
				]
			},
			"multivalued": false
		},
		{
			"name": "lastName",
			"displayName": "${lastName}",
			"validations": {
				"length": {
					"max": 255
				},
				"person-name-prohibited-characters": {}
			},
			"required": {
				"roles": [
					"user"
				]
			},
			"permissions": {
				"view": [
					"admin",
					"user"
				],
				"edit": [
					"admin",
					"user"
				]
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
}
';

    public function testItCanFetchTheProfileStructure(): void
    {
        $this->client->expects($this->once())
            ->method('request')
            ->with('GET', 'realms/{realm}/hawk/profile/structure')
            ->willReturn($this->createStreamResponse(self::RESPONSE));

        $structure = $this->api->fetchProfileStructure();

        $this->assertNotNull($structure);
        $this->assertCount(4, iterator_to_array($structure->getFields($this->config, true)));
        $this->assertCount(1, iterator_to_array($structure->getGroups($this->config, true)));
    }

    public function testItFailsToFetchProfileIfUnauthorized(): void
    {
        $this->client->expects($this->once())
            ->method('request')
            ->willThrowException($this->createClientNotAuthorizedException());

        $this->expectException(FailedRequestDueToMissingPermissionsException::class);
        $this->expectExceptionMessage('"hawk-view-profile-structure"');

        $this->api->fetchProfileStructure();
    }

    public function testItFailsWithTheDefaultBehaviourOnOtherExceptions(): void
    {
        $exception = $this->createClientException(self::RESPONSE);

        $this->client->expects($this->once())
            ->method('request')
            ->willThrowException($exception);

        $this->expectExceptionObject($exception);

        $this->api->fetchProfileStructure();
    }

}
