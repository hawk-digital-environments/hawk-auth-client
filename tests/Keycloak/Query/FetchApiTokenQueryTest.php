<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Keycloak\Query;

use Hawk\AuthClient\Exception\ApiTokenRequestFailedException;
use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Keycloak\Query\FetchApiTokenQuery;
use Hawk\AuthClient\Keycloak\Value\ApiToken;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FetchApiTokenQuery::class)]
#[CoversClass(KeycloakApiClient::class)]
#[CoversClass(ApiTokenRequestFailedException::class)]
class FetchApiTokenQueryTest extends KeycloakQueryTestCase
{
    public function testItCanRetrieveAnApiToken(): void
    {
        $this->clock->method('now')->willReturn(new \DateTimeImmutable('2021-01-01T00:00:00Z'));
        $this->client->expects($this->once())
            ->method('request')
            ->with('POST', 'realms/{realm}/protocol/openid-connect/token', [
                'form_params' => [
                    'client_id' => 'CLIENT_ID',
                    'client_secret' => 'CLIENT_SECRET',
                    'grant_type' => 'client_credentials',
                    'scope' => 'openid'
                ],
            ])
            ->willReturn($this->createStreamResponse('{"access_token":"TOKEN","expires_in":3600}'));
        $token = $this->api->fetchApiToken();
        $this->assertInstanceOf(ApiToken::class, $token);
        $this->assertEquals('TOKEN', (string)$token);
        $this->assertEquals(new \DateTimeImmutable('2021-01-01T01:00:00Z'), $token->getExpiresAt());
    }

    public function testItThrowsExceptionOnFailure(): void
    {
        $this->client->method('request')
            ->willThrowException($this->createClientException());
        $this->expectException(ApiTokenRequestFailedException::class);
        $this->api->fetchApiToken();
    }

}
