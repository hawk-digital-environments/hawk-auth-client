<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Keycloak;


use Hawk\AuthClient\Cache\CacheAdapterInterface;
use Hawk\AuthClient\Clock\SystemClock;
use Hawk\AuthClient\Keycloak\ApiTokenStorage;
use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Keycloak\Value\ApiToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

#[CoversClass(ApiTokenStorage::class)]
class ApiTokenStorageTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new ApiTokenStorage(
            $this->createStub(CacheAdapterInterface::class),
            $this->createStub(ClockInterface::class),
            $this->createStub(KeycloakApiClient::class)
        );
        $this->assertInstanceOf(ApiTokenStorage::class, $sut);
    }

    public function testItCanRequestANotExpiredToken(): void
    {
        $now = new \DateTimeImmutable();
        $token = $this->createMock(ApiToken::class);
        $token->expects($this->exactly(2))->method('isExpired')->with($now)->willReturn(false);
        $cache = $this->createMock(CacheAdapterInterface::class);
        $cache->expects($this->once())->method('remember')->willReturn($token);
        $cache->expects($this->never())->method('delete');
        $clock = new SystemClock($now);
        $sut = new ApiTokenStorage($cache, $clock, $this->createStub(KeycloakApiClient::class));

        $this->assertSame($token, $sut->getToken());
        // The second call should return the same token, but remember was only called once
        $this->assertSame($token, $sut->getToken());
    }

    public function testItAutomaticallyRefreshesTheTokenOnExpiry(): void
    {
        $now = new \DateTimeImmutable();
        $token = $this->createMock(ApiToken::class);
        $token->expects($this->exactly(2))->method('isExpired')->with($now)->willReturnOnConsecutiveCalls(false, true);
        $refreshedToken = $this->createMock(ApiToken::class);
        $refreshedToken->expects($this->exactly(2))->method('isExpired')->with($now)->willReturn(false);

        $cache = $this->createMock(CacheAdapterInterface::class);
        $cache->expects($this->exactly(2))->method('remember')
            ->willReturn($token, $refreshedToken);
        $cache->expects($this->once())->method('delete');
        $clock = new SystemClock($now);
        $sut = new ApiTokenStorage($cache, $clock, $this->createStub(KeycloakApiClient::class));

        // First request, should return the token
        $this->assertSame($token, $sut->getToken());
        // Second request, should return the refreshed token
        $this->assertSame($refreshedToken, $sut->getToken());
        // Third request, should return the refreshed token again
        $this->assertSame($refreshedToken, $sut->getToken());
    }

    public function testItCachesCorrectly(): void
    {
        $tokenExpiresAt = new \DateTimeImmutable('2021-01-01T00:00:00+00:00');
        $token = new ApiToken(
            'token',
            $tokenExpiresAt
        );
        $clock = new SystemClock(new \DateTimeImmutable('2020-12-31T23:59:20+00:00'));

        $api = $this->createMock(KeycloakApiClient::class);
        $api->expects($this->once())->method('fetchApiToken')->willReturn($token);

        $cache = $this->createMock(CacheAdapterInterface::class);
        $cache->expects($this->once())->method('remember')
            ->willReturnCallback(
                function (
                    $key,
                    $valueGenerator,
                    $valueToCache,
                    $cacheToValue,
                    $ttl
                ) use ($token) {
                    $this->assertSame('keycloak.client.api_token', $key);
                    $this->assertSame($token, $valueGenerator());
                    $this->assertEquals($token, $cacheToValue($valueToCache($token)));
                    $this->assertSame(10, $ttl(new ApiToken('foo', new \DateTimeImmutable('2021-01-01T00:00:00+00:00'))));

                    return $token;
                }
            );
        $sut = new ApiTokenStorage($cache, $clock, $api);

        $this->assertSame($token, $sut->getToken());
    }

}
