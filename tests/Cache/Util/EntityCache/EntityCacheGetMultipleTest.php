<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Cache\Util\EntityCache;

use Hawk\AuthClient\Cache\CacheAdapterInterface;
use Hawk\AuthClient\Cache\Util\AbstractEntityCache;
use Hawk\AuthClient\Tests\TestUtils\DummyUuid;
use Hawk\AuthClient\Util\Uuid;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(AbstractEntityCache::class, 'getAllByIds')]
class EntityCacheGetMultipleTest extends EntityCacheTestCase
{
    public function testItCanGetMultipleWithNothingCached(): void
    {
        $id1 = new DummyUuid(1);
        $id2 = new DummyUuid(2);
        $id3 = new DummyUuid(3);
        $v1 = (object)['value' => 1];
        $v2 = (object)['value' => 2];
        $v3 = (object)['value' => 3];
        $ids = [$id1, $id2, $id3];
        $items = [(string)$id1 => $v1, (string)$id2 => $v2, (string)$id3 => $v3];

        $cache = $this->createMock(CacheAdapterInterface::class);
        $readInvoker = $this->exactly(3);
        $cache->expects($readInvoker)
            ->method('get')
            ->with($this->callback(function ($id) use ($readInvoker, $id1, $id2, $id3) {
                return match ($readInvoker->numberOfInvocations()) {
                    1 => $id === (string)$id1,
                    2 => $id === (string)$id2,
                    3 => $id === (string)$id3,
                };
            }))
            ->willReturn(null);

        $setInvoker = $this->exactly(3);
        $cache->expects($setInvoker)
            ->method('set')
            ->willReturnCallback(function ($k, $v, $ttl) use ($setInvoker, $id1, $id2, $id3) {
                $this->assertEquals(null, $ttl);
                switch ($setInvoker->numberOfInvocations()) {
                    case 1:
                        $this->assertEquals((string)$id1, $k);
                        $this->assertEquals(['v' => 'O:8:"stdClass":1:{s:5:"value";i:1;}'], $v);
                        break;
                    case 2:
                        $this->assertEquals((string)$id2, $k);
                        $this->assertEquals(['v' => 'O:8:"stdClass":1:{s:5:"value";i:2;}'], $v);
                        break;
                    case 3:
                        $this->assertEquals((string)$id3, $k);
                        $this->assertEquals(['v' => 'O:8:"stdClass":1:{s:5:"value";i:3;}'], $v);
                        break;
                }
            });

        $fetchItems = function (Uuid ...$givenIds) use ($items, $ids): iterable {
            $this->assertEquals($ids, $givenIds);
            foreach ($ids as $id) {
                yield $id => $items[(string)$id];
            }
        };

        $sut = $this->createSut($cache, fetchItems: $fetchItems);
        $result = iterator_to_array($sut->getAllByIds(...$ids), false);
        $this->assertEquals([$v1, $v2, $v3], $result);

        // Special check -> Reuse locally stored items (should not modify the counters)
        $result = iterator_to_array($sut->getAllByIds(...$ids), false);
        $this->assertEquals([$v1, $v2, $v3], $result);
    }

    public function testItCanGetMultipleWithPartiallyCachedValues(): void
    {
        $id1 = new DummyUuid(1);
        $id2 = new DummyUuid(2);
        $id3 = new DummyUuid(3);
        $v1 = (object)['value' => 1];
        $v2 = (object)['value' => 2];
        $v3 = (object)['value' => 3];
        $ids = [$id1, $id2, $id3];
        $items = [(string)$id1 => $v1, (string)$id2 => $v2, (string)$id3 => $v3];

        $unserializeItems = function ($id, $data) use ($id1, $v1) {
            $this->assertEquals(['CACHED'], $data);
            $this->assertEquals($id1, $id);
            return $v1;
        };

        $fetchItems = function (Uuid ...$ids) use ($items, $id2, $id3): iterable {
            $this->assertEquals([$id2, $id3], $ids);
            yield $id2 => $items[(string)$id2];
            yield $id3 => $items[(string)$id3];
        };

        $cache = $this->createMock(CacheAdapterInterface::class);

        $readInvoker = $this->exactly(3);
        $cache->expects($readInvoker)
            ->method('get')
            ->with($this->callback(function ($id) use ($readInvoker, $id1, $id2, $id3) {
                return match ($readInvoker->numberOfInvocations()) {
                    1 => $id === (string)$id1,
                    2 => $id === (string)$id2,
                    3 => $id === (string)$id3,
                };
            }))
            ->willReturnCallback(function ($id) use ($id1, $id2, $id3) {
                return match ($id) {
                    (string)$id1 => ['CACHED'],
                    (string)$id2, (string)$id3 => null,
                };
            });

        $sut = $this->createSut($cache, fetchItems: $fetchItems, unserialize: $unserializeItems);
        $result = iterator_to_array($sut->getAllByIds(...$ids), false);
        $this->assertEquals([$v1, $v2, $v3], $result);

        // Special check -> Reuse locally stored items (should not modify the counters)
        $result = iterator_to_array($sut->getAllByIds(...$ids), false);
        $this->assertEquals([$v1, $v2, $v3], $result);
    }

    public function testItCanGetMultipleWithAllCachedValues(): void
    {
        $id1 = new DummyUuid(1);
        $id2 = new DummyUuid(2);
        $id3 = new DummyUuid(3);
        $v1 = (object)['value' => 1];
        $v2 = (object)['value' => 2];
        $v3 = (object)['value' => 3];
        $ids = [$id1, $id2, $id3];

        $unserializeItems = function ($id, $data) use ($v1, $v2, $v3, $id1, $id2, $id3) {
            $this->assertEquals(['CACHED'], $data);
            return match ($id) {
                $id1 => $v1,
                $id2 => $v2,
                $id3 => $v3,
            };
        };

        $cache = $this->createMock(CacheAdapterInterface::class);

        $readInvoker = $this->exactly(3);
        $cache->expects($readInvoker)
            ->method('get')
            ->with($this->callback(function ($id) use ($readInvoker, $id1, $id2, $id3) {
                return match ($readInvoker->numberOfInvocations()) {
                    1 => $id === (string)$id1,
                    2 => $id === (string)$id2,
                    3 => $id === (string)$id3,
                };
            }))
            ->willReturn(['CACHED']);
        $cache->expects($this->never())->method('set');

        $sut = $this->createSut($cache, unserialize: $unserializeItems);
        $result = iterator_to_array($sut->getAllByIds(...$ids), false);
        $this->assertEquals([$v1, $v2, $v3], $result);

        // Special check -> Reuse locally stored items (should not modify the counters)
        $result = iterator_to_array($sut->getAllByIds(...$ids), false);
        $this->assertEquals([$v1, $v2, $v3], $result);
    }
}
