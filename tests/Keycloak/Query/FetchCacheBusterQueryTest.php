<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Keycloak\Query;


use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Keycloak\Query\FetchCacheBusterQuery;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FetchCacheBusterQuery::class)]
#[CoversClass(KeycloakApiClient::class)]
class FetchCacheBusterQueryTest extends KeycloakQueryTestCase
{
    public function testItCanFetchTheCacheBuster(): void
    {
        $this->client->expects($this->once())
            ->method('request')
            ->with('GET', 'realms/{realm}/hawk/cache-buster')
            ->willReturn($this->createStreamResponse('1234567890'));

        $buster = $this->api->fetchCacheBuster();
        $this->assertEquals('1234567890', (string)$buster);
    }

    public function testIfTheRequestFailsItWillReturnTheNowTimestampAsCacheBuster(): void
    {
        $now = new \DateTimeImmutable();
        $this->clock->method('now')->willReturn($now);
        $this->client->method('request')
            ->willThrowException($this->createClientException());
        $buster = $this->api->fetchCacheBuster();
        $this->assertEquals($now->getTimestamp(), (string)$buster);
    }

}
