<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Cache\Util;


use Hawk\AuthClient\Cache\CacheAdapterInterface;
use Hawk\AuthClient\Cache\Util\ConnectionConfigAwareCache;
use Hawk\AuthClient\Keycloak\Value\ConnectionConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConnectionConfigAwareCache::class)]
class ConnectionConfigAwareCacheTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new ConnectionConfigAwareCache($this->createStub(CacheAdapterInterface::class), $this->createStub(ConnectionConfig::class));
        $this->assertInstanceOf(ConnectionConfigAwareCache::class, $sut);
    }

    public function testItGeneratesTheCacheKeyIncludingTheConnectionConfig(): void
    {
        $connectionConfig = $this->createStub(ConnectionConfig::class);
        $connectionConfig->method('getHash')->willReturn('hash');

        $cache = $this->createMock(CacheAdapterInterface::class);
        $cache->expects($this->once())
            ->method('get')
            ->with('foo-hash');

        $sut = new ConnectionConfigAwareCache($cache, $connectionConfig);
        $sut->get('foo');
    }
}
