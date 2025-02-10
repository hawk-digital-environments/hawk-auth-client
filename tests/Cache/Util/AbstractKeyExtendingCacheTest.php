<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Cache\Util;


use Hawk\AuthClient\Cache\CacheAdapterInterface;
use Hawk\AuthClient\Cache\Util\AbstractKeyExtendingCache;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractKeyExtendingCache::class)]
class AbstractKeyExtendingCacheTest extends TestCase
{
    protected CacheAdapterInterface $cacheMock;
    protected AbstractKeyExtendingCache $sut;

    protected function setUp(): void
    {
        $this->cacheMock = $this->createMock(CacheAdapterInterface::class);
        $this->sut = new class($this->cacheMock) extends AbstractKeyExtendingCache {
            protected function extendKey(string $key): string
            {
                return 'ext_' . $key;
            }
        };
    }

    public function testItConstructs(): void
    {
        $this->assertInstanceOf(AbstractKeyExtendingCache::class, $this->sut);
    }

    public function testItExtendsTheKeyOnGet(): void
    {
        $this->cacheMock->expects($this->once())
            ->method('get')
            ->with('ext_foo')
            ->willReturn(null);
        $this->sut->get('foo');
    }

    public function testItExtendsTheKeyOnSet(): void
    {
        $this->cacheMock->expects($this->once())
            ->method('set')
            ->with('ext_foo', 'bar', null);
        $this->sut->set('foo', 'bar');
    }

    public function testItExtendsTheKeyOnDelete(): void
    {
        $this->cacheMock->expects($this->once())
            ->method('delete')
            ->with('ext_foo');
        $this->sut->delete('foo');
    }

    public function testItExtendsTheKeyOnRemember(): void
    {
        $this->cacheMock->expects($this->once())
            ->method('remember')
            ->with('ext_foo', $this->isCallable(), null, null, null);
        $this->sut->remember('foo', fn() => 'bar');
    }

}
