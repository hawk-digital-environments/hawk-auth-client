<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Cache\Util\EntityCache;

use Hawk\AuthClient\Cache\CacheAdapterInterface;
use Hawk\AuthClient\Cache\Util\AbstractEntityCache;
use Hawk\AuthClient\Tests\TestUtils\DummyUuid;
use Hawk\AuthClient\Util\Uuid;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(AbstractEntityCache::class, 'getOne')]
class EntitycacheGetSingleTest extends EntityCacheTestCase
{
    public function testItCanGetSingleWithNothingCached(): void
    {
        $v1 = (object)['value' => 1];
        $id = new DummyUuid();

        $cache = $this->createMock(CacheAdapterInterface::class);
        $cache->expects($this->once())
            ->method('get')
            ->with($id)
            ->willReturn(null);

        $cache->expects($this->once())
            ->method('set')
            ->with($id, ['v' => 'O:8:"stdClass":1:{s:5:"value";i:1;}'], null);

        $fetchItems = function (Uuid ...$ids) use ($v1, $id): iterable {
            $this->assertEquals([$id], $ids);
            yield $id => $v1;
        };

        $sut = $this->createSut(cache: $cache, fetchItems: $fetchItems);
        $this->assertEquals($v1, $sut->getOne($id));

        // It should be cached now (counter test will fail if it's not)
        $this->assertEquals($v1, $sut->getOne($id));
    }

    public function testItCanGetSingleWithItemCached(): void
    {
        $v1 = (object)['value' => 1];
        $id = new DummyUuid();

        $cache = $this->createMock(CacheAdapterInterface::class);
        $cache->expects($this->once())
            ->method('get')
            ->with($id)
            ->willReturn(['v' => 'O:8:"stdClass":1:{s:5:"value";i:1;}']);

        $fetchItems = function (): array {
            $this->fail('Should not fetch items');
        };

        $sut = $this->createSut(cache: $cache, fetchItems: $fetchItems);
        $this->assertEquals($v1, $sut->getOne($id));

        // It should be cached now (counter test will fail if it's not)
        $this->assertEquals($v1, $sut->getOne($id));
    }

    public function testItReturnsNullIfItemWasNotFound(): void
    {
        $id = new DummyUuid();

        $cache = $this->createMock(CacheAdapterInterface::class);
        $cache->expects($this->once())
            ->method('get')
            ->with($id)
            ->willReturn(null);

        $fetchItems = function (Uuid ...$ids) use ($id): array {
            $this->assertEquals([$id], $ids);
            return [];
        };

        $cache->expects($this->once())
            ->method('set')
            ->with($id, false, null);

        $sut = $this->createSut(cache: $cache, fetchItems: $fetchItems);
        $this->assertNull($sut->getOne($id));
    }

    public function testItReturnsNullIfAPreviousFetchDidNotFindAnItem(): void
    {
        $id = new DummyUuid();

        $cache = $this->createMock(CacheAdapterInterface::class);
        $cache->expects($this->once())
            ->method('get')
            ->with($id)
            ->willReturn(false);

        $fetchItems = function (): array {
            $this->fail('Should not fetch items');
        };

        $sut = $this->createSut(cache: $cache, fetchItems: $fetchItems);
        $this->assertNull($sut->getOne($id));
    }

    public function testItReturnsNullIfAnItemIsNotCachedButItShouldNotFetchMissing(): void
    {
        $id = new DummyUuid();

        $cache = $this->createMock(CacheAdapterInterface::class);
        $cache->expects($this->once())
            ->method('get')
            ->with($id)
            ->willReturn(null);

        $fetchItems = function (): array {
            $this->fail('Should not fetch items');
        };

        $sut = $this->createSut(cache: $cache, fetchItems: $fetchItems);
        $this->assertNull($sut->getOne($id, false));
    }
}
