<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Users\Value;


use Hawk\AuthClient\Tests\TestUtils\DummyUuid;
use Hawk\AuthClient\Users\Value\UserConstraints;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UserConstraints::class)]
class UserConstraintsTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new UserConstraints();
        $this->assertInstanceOf(UserConstraints::class, $sut);
    }

    public function testItCanSetAndCheckOnlyOnline(): void
    {
        $sut = new UserConstraints();
        $this->assertFalse($sut->onlyOnline());
        $sut2 = $sut->withOnlyOnline();
        $this->assertNotSame($sut, $sut2);
        $this->assertTrue($sut2->onlyOnline());
        $this->assertFalse($sut->onlyOnline());
    }

    public function testItCanSetAndGetSearchFilter(): void
    {
        $sut = new UserConstraints();
        $this->assertNull($sut->getSearch());
        $sut2 = $sut->withSearch('foo');
        $this->assertNotSame($sut, $sut2);
        $this->assertSame('foo', $sut2->getSearch());
        $this->assertNull($sut->getSearch());
    }

    public function testItCanGetSetAndRemoveAttributeFilters(): void
    {
        $sut = new UserConstraints();
        $this->assertEmpty($sut->getAttributes());
        $sut2 = $sut->withAttribute('foo', 'bar');
        $this->assertNotSame($sut, $sut2);
        $sut3 = $sut2->withAttribute('bar', 'baz');
        $this->assertNotSame($sut2, $sut3);
        $this->assertSame(['foo' => 'bar', 'bar' => 'baz'], $sut3->getAttributes());
        $sut3 = $sut2->withoutAttribute('bar');
        $this->assertNotSame($sut2, $sut3);
        $this->assertSame(['foo' => 'bar'], $sut3->getAttributes());
        $this->assertEmpty($sut->getAttributes());
    }

    public function testItCanGetAndSetIdFilter(): void
    {
        $id1 = new DummyUuid(1);
        $id2 = new DummyUuid(2);
        $sut = new UserConstraints();
        $this->assertEmpty($sut->getIds());
        $sut2 = $sut->withIds($id1, $id2, $id1);
        $this->assertNotSame($sut, $sut2);
        $this->assertSame([$id1, $id2], $sut2->getIds());
        $this->assertEmpty($sut->getIds());
    }
}
