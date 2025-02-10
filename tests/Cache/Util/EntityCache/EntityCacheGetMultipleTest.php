<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Cache\Util\EntityCache;

use Hawk\AuthClient\Cache\CacheAdapterInterface;
use Hawk\AuthClient\Cache\Util\AbstractEntityCache;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(AbstractEntityCache::class, 'getAllByIds')]
class EntityCacheGetMultipleTest extends EntityCacheTestCase
{
    public function testItCanGetMultipleWithNothingCached(): void
    {
        $v1 = (object)['value' => 1];
        $v2 = (object)['value' => 2];
        $v3 = (object)['value' => 3];
        $ids = ['foo', 'bar', 'baz'];
        $items = ['foo' => $v1, 'bar' => $v2, 'baz' => $v3];

        $cache = $this->createMock(CacheAdapterInterface::class);
        $readInvoker = $this->exactly(3);
        $cache->expects($readInvoker)
            ->method('get')
            ->with($this->callback(function ($id) use ($readInvoker) {
                return match ($readInvoker->numberOfInvocations()) {
                    1 => $id === 'foo',
                    2 => $id === 'bar',
                    3 => $id === 'baz',
                };
            }))
            ->willReturn(null);

        $setInvoker = $this->exactly(3);
        $cache->expects($setInvoker)
            ->method('set')
            ->willReturnCallback(function ($k, $v, $ttl) use ($setInvoker) {
                $this->assertEquals(null, $ttl);
                switch ($setInvoker->numberOfInvocations()) {
                    case 1:
                        $this->assertEquals('foo', $k);
                        $this->assertEquals(['v' => 'O:8:"stdClass":1:{s:5:"value";i:1;}'], $v);
                        break;
                    case 2:
                        $this->assertEquals('bar', $k);
                        $this->assertEquals(['v' => 'O:8:"stdClass":1:{s:5:"value";i:2;}'], $v);
                        break;
                    case 3:
                        $this->assertEquals('baz', $k);
                        $this->assertEquals(['v' => 'O:8:"stdClass":1:{s:5:"value";i:3;}'], $v);
                        break;
                }
            });

        $fetchItems = function (string ...$ids) use ($items): array {
            $this->assertEquals(['foo', 'bar', 'baz'], $ids);
            return array_intersect_key($items, array_flip($ids));
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
        $v1 = (object)['value' => 1];
        $v2 = (object)['value' => 2];
        $v3 = (object)['value' => 3];
        $ids = ['foo', 'bar', 'baz'];
        $items = ['foo' => $v1, 'bar' => $v2, 'baz' => $v3];

        $unserializeItems = function ($id, $data) use ($v1) {
            $this->assertEquals(['CACHED'], $data);
            $this->assertEquals('foo', $id);
            return $v1;
        };

        $fetchItems = function (string ...$ids) use ($items): array {
            $this->assertEquals(['bar', 'baz'], $ids);
            return array_intersect_key($items, array_flip($ids));
        };

        $cache = $this->createMock(CacheAdapterInterface::class);

        $readInvoker = $this->exactly(3);
        $cache->expects($readInvoker)
            ->method('get')
            ->with($this->callback(function ($id) use ($readInvoker) {
                return match ($readInvoker->numberOfInvocations()) {
                    1 => $id === 'foo',
                    2 => $id === 'bar',
                    3 => $id === 'baz',
                };
            }))
            ->willReturnCallback(function ($id) {
                return match ($id) {
                    'foo' => ['CACHED'],
                    'bar', 'baz' => null,
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
        $v1 = (object)['value' => 1];
        $v2 = (object)['value' => 2];
        $v3 = (object)['value' => 3];
        $ids = ['foo', 'bar', 'baz'];

        $unserializeItems = function ($id, $data) use ($v1, $v2, $v3) {
            $this->assertEquals(['CACHED'], $data);
            return match ($id) {
                'foo' => $v1,
                'bar' => $v2,
                'baz' => $v3,
            };
        };

        $cache = $this->createMock(CacheAdapterInterface::class);

        $readInvoker = $this->exactly(3);
        $cache->expects($readInvoker)
            ->method('get')
            ->with($this->callback(function ($id) use ($readInvoker) {
                return match ($readInvoker->numberOfInvocations()) {
                    1 => $id === 'foo',
                    2 => $id === 'bar',
                    3 => $id === 'baz',
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
