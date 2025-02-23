<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Keycloak;


use Hawk\AuthClient\Cache\CacheAdapterInterface;
use Hawk\AuthClient\Keycloak\ConnectionInfoStorage;
use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Keycloak\Value\ConnectionInfo;
use Hawk\AuthClient\Tests\TestUtils\DummyUuid;
use Hawk\AuthClient\Util\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConnectionInfoStorage::class)]
class ConnectionInfoStorageTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new ConnectionInfoStorage(
            $this->createStub(CacheAdapterInterface::class),
            $this->createStub(KeycloakApiClient::class)
        );
        $this->assertInstanceOf(ConnectionInfoStorage::class, $sut);
    }

    public function testItReturnsTheConnectionInfo(): void
    {
        $info = $this->createStub(ConnectionInfo::class);
        $cache = $this->createMock(CacheAdapterInterface::class);
        $cache->expects($this->once())->method('remember')->willReturn($info);
        $sut = new ConnectionInfoStorage($cache, $this->createStub(KeycloakApiClient::class));

        $this->assertSame($info, $sut->getConnectionInfo());
        // Should be cached locally now
        $this->assertSame($info, $sut->getConnectionInfo());
    }

    public function testItCachesCorrectly(): void
    {
        $info = new ConnectionInfo(
            'foo123',
            'bar456',
            'baz789',
            new Uuid(new DummyUuid(1)),
            new Uuid(new DummyUuid(2))
        );
        $api = $this->createMock(KeycloakApiClient::class);
        $api->expects($this->once())->method('fetchConnectionInfo')->willReturn($info);
        $cache = $this->createMock(CacheAdapterInterface::class);
        $cache->expects($this->once())->method('remember')->willReturnCallback(function (
            $key,
            $valueGenerator,
            $valueToCache,
            $cacheToValue,
        ) use ($info) {
            $this->assertSame('keycloak.client.connection_info', $key);
            $this->assertSame($info, $valueGenerator());
            $this->assertEquals($info, $cacheToValue($valueToCache($info)));
            return $info;
        });

        $sut = new ConnectionInfoStorage($cache, $api);

        $this->assertSame($info, $sut->getConnectionInfo());
    }
}
