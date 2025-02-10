<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Cache;


use Hawk\AuthClient\Cache\SimpleCacheAdapter;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

#[CoversMethod(SimpleCacheAdapter::class, '__construct')]
#[CoversMethod(SimpleCacheAdapter::class, 'get')]
#[CoversMethod(SimpleCacheAdapter::class, 'set')]
#[CoversMethod(SimpleCacheAdapter::class, 'delete')]
class SimpleCacheAdapterTest extends TestCase
{
    protected CacheInterface $cacheMock;
    protected SimpleCacheAdapter $sut;

    protected function setUp(): void
    {
        $this->cacheMock = $this->createMock(CacheInterface::class);
        $this->sut = new SimpleCacheAdapter($this->cacheMock);
    }

    public function testItConstructs(): void
    {
        $this->assertInstanceOf(SimpleCacheAdapter::class, $this->sut);
    }

    public function testItCanGet(): void
    {
        $this->cacheMock->expects($this->once())
            ->method('get')
            ->with('foo')
            ->willReturn('bar');
        $this->assertEquals('bar', $this->sut->get('foo'));
    }

    public function testItCanSet(): void
    {
        $this->cacheMock->expects($this->once())
            ->method('set')
            ->with('foo', 'bar', 60)
            ->willReturn(true);
        $this->sut->set('foo', 'bar', 60);
    }

    public function testItCanDelete(): void
    {
        $this->cacheMock->expects($this->once())
            ->method('delete')
            ->with('foo')
            ->willReturn(true);
        $this->sut->delete('foo');
    }

    public function testItCanRemember(): void
    {
        $this->cacheMock->expects($this->once())
            ->method('get')
            ->with('foo')
            ->willReturn(null);
        $this->cacheMock->expects($this->once())
            ->method('set')
            ->with('foo', 'bar', 60)
            ->willReturn(true);
        $this->assertEquals('bar', $this->sut->remember(
            'foo',
            valueGenerator: fn() => 'bar',
            ttl: 60)
        );
    }
}
