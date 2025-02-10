<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Keycloak;


use Hawk\AuthClient\Cache\CacheAdapterInterface;
use Hawk\AuthClient\Keycloak\CacheBusterStorage;
use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Keycloak\Value\CacheBuster;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CacheBusterStorage::class)]
class CacheBusterStorageTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new CacheBusterStorage(
            $this->createStub(CacheAdapterInterface::class),
            $this->createStub(KeycloakApiClient::class)
        );
        $this->assertInstanceOf(CacheBusterStorage::class, $sut);
    }

    public function testItCanGetACacheBuster(): void
    {
        $cacheBuster = $this->createStub(CacheBuster::class);
        $cache = $this->createMock(CacheAdapterInterface::class);
        $cache->expects($this->once())->method('remember')->willReturn($cacheBuster);
        $sut = new CacheBusterStorage($cache, $this->createStub(KeycloakApiClient::class));

        $this->assertSame($cacheBuster, $sut->getCacheBuster());
        // Should be cached locally now
        $this->assertSame($cacheBuster, $sut->getCacheBuster());
    }

    public function testItCachesCorrectly(): void
    {
        $cacheBuster = new CacheBuster('foo123');
        $api = $this->createMock(KeycloakApiClient::class);
        $api->expects($this->once())->method('fetchCacheBuster')->willReturn($cacheBuster);
        $cache = $this->createMock(CacheAdapterInterface::class);
        $cache->expects($this->once())->method('remember')
            ->willReturnCallback(function (
                $key,
                $valueGenerator,
                $valueToCache,
                $cacheToValue,
                $ttl
            ) use ($cacheBuster) {
                $this->assertSame('keycloak.client.cacheBreaker', $key);
                $this->assertSame($cacheBuster, $valueGenerator());
                $this->assertSame(10, $ttl);
                $this->assertSame((string)$cacheBuster, $valueToCache($cacheBuster));
                $this->assertEquals($cacheBuster, $cacheToValue((string)$cacheBuster));
                return $cacheBuster;
            });

        $sut = new CacheBusterStorage($cache, $api);
        $this->assertSame($cacheBuster, $sut->getCacheBuster());
    }

    public function testItCanFlushTheStorage(): void
    {
        $cacheBuster = $this->createStub(CacheBuster::class);
        $cache = $this->createMock(CacheAdapterInterface::class);
        $cache->expects($this->exactly(2))->method('remember')->willReturn($cacheBuster);
        $cache->expects($this->once())->method('delete');
        $sut = new CacheBusterStorage($cache, $this->createStub(KeycloakApiClient::class));

        $this->assertSame($cacheBuster, $sut->getCacheBuster());
        $sut->flush();
        $this->assertSame($cacheBuster, $sut->getCacheBuster());
    }

}
