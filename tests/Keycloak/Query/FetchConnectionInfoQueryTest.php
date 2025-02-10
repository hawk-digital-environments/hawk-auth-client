<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Keycloak\Query;

use Hawk\AuthClient\Exception\ConnectionInfoRequestFailedException;
use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Keycloak\Query\FetchConnectionInfoQuery;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FetchConnectionInfoQuery::class)]
#[CoversClass(ConnectionInfoRequestFailedException::class)]
#[CoversClass(KeycloakApiClient::class)]
class FetchConnectionInfoQueryTest extends KeycloakQueryTestCase
{
    public function testItCanFetchTheConnectionInfo(): void
    {
        $this->client->expects($this->once())
            ->method('request')
            ->with('GET', 'realms/{realm}/hawk/connection-info')
            ->willReturn($this->createStreamResponse('{
	"keycloakVersion": "26.1.0",
	"extensionVersion": "0.0.1",
	"clientId": "hawk",
	"clientUuid": "643fd89d-b519-413f-a345-2abc43a7cc05"
}'));
        $info = $this->api->fetchConnectionInfo();
        $this->assertEquals('26.1.0', $info->getKeycloakVersion());
        $this->assertEquals('0.0.1', $info->getExtensionVersion());
        $this->assertEquals('hawk', $info->getClientId());
        $this->assertEquals('643fd89d-b519-413f-a345-2abc43a7cc05', (string)$info->getClientUuid());
    }

    public function testItThrowsExceptionOnFailure(): void
    {
        $this->client->method('request')
            ->willThrowException($this->createClientException());
        $this->expectException(ConnectionInfoRequestFailedException::class);
        $this->api->fetchConnectionInfo();
    }
}
