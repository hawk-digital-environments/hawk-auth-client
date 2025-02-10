<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Cache\Util;

use Hawk\AuthClient\Cache\CacheAdapterInterface;
use Hawk\AuthClient\Cache\Util\CacheBusterAwareCache;
use Hawk\AuthClient\Keycloak\CacheBusterStorage;
use Hawk\AuthClient\Keycloak\Value\CacheBuster;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CacheBusterAwareCache::class)]
class CacheBusterAwareCacheTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new CacheBusterAwareCache($this->createStub(CacheAdapterInterface::class), $this->createStub(CacheBusterStorage::class));
        $this->assertinstanceOf(CacheBusterAwareCache::class, $sut);
    }

    public function testItGeneratesTheCacheKeyIncludingTheCacheBuster(): void
    {
        $cache = $this->createMock(CacheAdapterInterface::class);
        $cache->expects($this->once())
            ->method('get')
            ->with('foo-123');

        $cacheBuster = new CacheBuster('123');
        $cacheBusterStorage = $this->createStub(CacheBusterStorage::class);
        $cacheBusterStorage->method('getCacheBuster')
            ->willReturn($cacheBuster);

        $sut = new CacheBusterAwareCache($cache, $cacheBusterStorage);
        $sut->get('foo');
    }
}
