<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Cache\Util\EntityCache;


use Hawk\AuthClient\Cache\CacheAdapterInterface;
use Hawk\AuthClient\Cache\Util\AbstractEntityCache;
use Hawk\AuthClient\Tests\TestUtils\DummyUuid;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(AbstractEntityCache::class, '__construct')]
#[CoversMethod(AbstractEntityCache::class, 'getCacheTtl')]
#[CoversMethod(AbstractEntityCache::class, 'save')]
#[CoversMethod(AbstractEntityCache::class, 'remove')]
#[CoversMethod(AbstractEntityCache::class, 'flushResolved')]
class EntityCacheTest extends EntityCacheTestCase
{
    public function testItConstructs(): void
    {
        $sut = $this->createSut();
        $this->assertInstanceOf(AbstractEntityCache::class, $sut);
    }

    public function testItReturnsTheDefaultTtl(): void
    {
        $id = new DummyUuid();
        $cache = $this->createMock(CacheAdapterInterface::class);
        $cache->expects($this->once())->method('set')->with((string)$id, $this->isArray(), null);
        $sut = $this->createSut(cache: $cache);
        $sut->save($id, new \stdClass());
    }

    public function testImplementationsMayOverrideTheTtlGenerator(): void
    {
        $id = new DummyUuid();
        $cache = $this->createMock(CacheAdapterInterface::class);
        $cache->expects($this->once())->method('set')->with($id, $this->isArray(), 60 * 60 * 24 * 7);
        $getCacheTtl = function ($givenId) use ($id) {
            $this->assertEquals($id, $givenId);
            return 60 * 60 * 24 * 7;
        };
        $sut = $this->createSut(cache: $cache, getCacheTtl: $getCacheTtl);
        $sut->save($id, new \stdClass());
    }

    public function testItCanSaveItems(): void
    {
        $getCacheKey = function ($id) {
            return $id . '_key';
        };

        $id = new DummyUuid();
        $v1 = (object)['value' => 1];
        $cache = $this->createMock(CacheAdapterInterface::class);
        $cache->expects($this->once())->method('set')->with($id . '_key', ['v' => 'O:8:"stdClass":1:{s:5:"value";i:1;}'], null);
        $sut = $this->createSut(cache: $cache, getCacheKey: $getCacheKey);
        $sut->save($id, $v1);

        // Test if "resolved" items are cached
        $this->assertEquals($v1, $sut->getOne($id, false));
    }

    public function testItCanRemoveItems(): void
    {
        $getCacheKey = function ($id) {
            return $id . '_key';
        };

        $id = new DummyUuid();
        $v1 = (object)['value' => 1];
        $cache = $this->createMock(CacheAdapterInterface::class);
        $cache->expects($this->once())->method('set')
            ->with($id . '_key', ['v' => 'O:8:"stdClass":1:{s:5:"value";i:1;}'], null);
        $cache->expects($this->once())->method('delete')->with($id . '_key');
        $cache->expects($this->once())->method('get')->with($id . '_key')->willReturn(null);
        $sut = $this->createSut(cache: $cache, getCacheKey: $getCacheKey);
        $sut->save($id, $v1);

        $sut->remove($id);

        // Resolved items should be removed -> would otherwise be fetched from cache
        $this->assertNull($sut->getOne($id, false));
    }

    public function testItCanFlushTheResolvedItems(): void
    {
        $cache = $this->createMock(CacheAdapterInterface::class);
        $cache->expects($this->exactly(2))->method('get')->willReturn(null);
        $sut = $this->createSut(cache: $cache);

        $id = new DummyUuid();
        
        $this->assertSame($sut->getOne($id), $sut->getOne($id));

        $sut->flushResolved();

        $this->assertSame($sut->getOne($id), $sut->getOne($id));
    }
}
