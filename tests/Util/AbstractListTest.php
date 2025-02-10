<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Util;


use Hawk\AuthClient\Util\AbstractList;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractList::class)]
class AbstractListTest extends TestCase
{
    protected const array DATA = [
        'foo',
        'bar',
        'baz'
    ];

    protected AbstractList $sut;

    protected function setUp(): void
    {
        $this->sut = new class(self::DATA) extends AbstractList {
            public function __construct(array $data)
            {
                $this->items = $data;
            }
        };
    }

    public function testItCanBeIterated(): void
    {
        $this->assertIsIterable($this->sut);
        $this->assertEquals(self::DATA, iterator_to_array($this->sut, false));
    }

    public function testItCanBeCounted(): void
    {
        $this->assertCount(count(self::DATA), $this->sut);
    }

    public function testItCanBeJsonSerialized(): void
    {
        $this->assertEquals(self::DATA, $this->sut->jsonSerialize());
    }
}
