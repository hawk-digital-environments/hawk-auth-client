<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Auth;


use Hawk\AuthClient\Auth\StatefulUserTokenStorage;
use Hawk\AuthClient\Keycloak\CacheBusterStorage;
use Hawk\AuthClient\Keycloak\Value\CacheBuster;
use Hawk\AuthClient\Session\SessionAdapterInterface;
use League\OAuth2\Client\Token\AccessTokenInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(StatefulUserTokenStorage::class)]
class StatefulUserTokenStorageTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new StatefulUserTokenStorage(
            $this->createStub(SessionAdapterInterface::class),
            $this->createStub(CacheBusterStorage::class)
        );
        $this->assertInstanceOf(StatefulUserTokenStorage::class, $sut);
    }

    public function testItReturnsNullIfNoTokenExists(): void
    {
        $session = $this->createStub(SessionAdapterInterface::class);
        $session->method('get')->willReturn(null);

        $sut = new StatefulUserTokenStorage(
            $session,
            $this->createStub(CacheBusterStorage::class)
        );

        $this->assertNull($sut->getToken());
    }

    public function testItReturnsAnExpiredTokenIfCacheBusterWasUpdated(): void
    {
        $oldTs = time() - 60 * 60;
        $newTs = time();

        $session = $this->createStub(SessionAdapterInterface::class);
        $session->method('get')->willReturnMap(
            [
                ['auth_token', ['access_token' => 'token', 'expires' => $newTs]],
                ['auth_cache_buster', (string)$oldTs]
            ]
        );

        $cacheBuster = $this->createStub(CacheBusterStorage::class);
        $cacheBuster->method('getCacheBuster')->willReturn(new CacheBuster((string)$newTs));

        $sut = new StatefulUserTokenStorage(
            $session,
            $cacheBuster
        );

        $token = $sut->getToken();
        $this->assertInstanceOf(AccessTokenInterface::class, $token);
        $this->assertTrue($token->hasExpired());
        $this->assertEquals(1349067601, $token->getExpires());
    }

    public function testItFlushesItselfIfTokenFailsToConstruct(): void
    {
        $ts = (string)time();
        $session = $this->createMock(SessionAdapterInterface::class);
        $session->method('get')->willReturnMap(
            [
                ['auth_token', ['expires' => time()]],
                ['auth_cache_buster', $ts]
            ]
        );

        $cacheBuster = $this->createStub(CacheBusterStorage::class);
        $cacheBuster->method('getCacheBuster')->willReturn(new CacheBuster($ts));

        $sut = new StatefulUserTokenStorage(
            $session,
            $cacheBuster
        );

        $this->assertNull($sut->getToken());
    }

    public function testItReturnsTokenIfNotOutdatedOrCacheBusterChanged(): void
    {
        $ts = (string)time();
        $session = $this->createMock(SessionAdapterInterface::class);
        $session->method('get')->willReturnMap(
            [
                ['auth_token', ['access_token' => 'token', 'expires' => (int)$ts + 60]],
                ['auth_cache_buster', $ts]
            ]
        );

        $cacheBuster = $this->createStub(CacheBusterStorage::class);
        $cacheBuster->method('getCacheBuster')->willReturn(new CacheBuster($ts));

        $sut = new StatefulUserTokenStorage(
            $session,
            $cacheBuster
        );

        $token = $sut->getToken();
        $this->assertInstanceOf(AccessTokenInterface::class, $token);
        $this->assertFalse($token->hasExpired());
    }

    public function testItCanSetTheToken(): void
    {
        $ts = (string)time();
        $token = $this->createStub(AccessTokenInterface::class);
        $tokenData = ['access_token' => 'token', 'expires' => (int)$ts + 60];
        $token->method('jsonSerialize')->willReturn($tokenData);
        $cacheBuster = $this->createStub(CacheBusterStorage::class);
        $cacheBuster->method('getCacheBuster')->willReturn(new CacheBuster($ts));
        $session = $this->createMock(SessionAdapterInterface::class);
        $session->expects($this->exactly(2))
            ->method('set')->with(
                $this->logicalOr(
                    $this->equalTo('auth_token'),
                    $this->equalTo('auth_cache_buster')
                ),
                $this->logicalOr(
                    $this->equalTo($tokenData),
                    $this->equalTo($ts)
                )
            );

        $sut = new StatefulUserTokenStorage(
            $session,
            $cacheBuster
        );

        $sut->setToken($token);
    }
}
