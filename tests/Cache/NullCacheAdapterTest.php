<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Cache;

use Hawk\AuthClient\Cache\NullCacheAdapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NullCacheAdapter::class)]
class NullCacheAdapterTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new NullCacheAdapter();
        $this->assertInstanceOf(NullCacheAdapter::class, $sut);
    }

    public function testItAlwaysReturnsNull(): void
    {
        $sut = new NullCacheAdapter();
        $this->assertNull($sut->get('foo'));
        $sut->set('foo', 'bar');
        $this->assertNull($sut->get('foo'));
    }

    public function testAllFunctionsCanBeCalled(): void
    {
        $sut = new NullCacheAdapter();
        $sut->delete('foo');
        $this->expectNotToPerformAssertions();
    }

}
