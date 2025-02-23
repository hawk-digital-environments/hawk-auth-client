<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Resources\Value;


use Hawk\AuthClient\Resources\Value\ResourceConstraints;
use Hawk\AuthClient\Tests\TestUtils\DummyUuid;
use Hawk\AuthClient\Users\Value\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ResourceConstraints::class)]
class ResourceConstraintsTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new ResourceConstraints();
        $this->assertInstanceOf(ResourceConstraints::class, $sut);
    }

    public function testItCanSetAndGetTheNameFilter(): void
    {
        $sut = new ResourceConstraints();
        $this->assertNull($sut->getName());
        $this->assertFalse($sut->isExactNames());

        $sut2 = $sut->withName('foo');
        $this->assertNotSame($sut, $sut2);
        $this->assertSame('foo', $sut2->getName());
        $this->assertFalse($sut2->isExactNames());
        $this->assertNull($sut->getName());

        $sut3 = $sut2->withName('bar', true);
        $this->assertNotSame($sut2, $sut3);
        $this->assertSame('bar', $sut3->getName());
        $this->assertTrue($sut3->isExactNames());
        $this->assertSame('foo', $sut2->getName());
        $this->assertFalse($sut2->isExactNames());

        $sut4 = $sut3->withName(null);
        $this->assertNotSame($sut3, $sut4);
        $this->assertNull($sut4->getName());
        $this->assertFalse($sut4->isExactNames());
        $this->assertSame('bar', $sut3->getName());
        $this->assertTrue($sut3->isExactNames());
    }

    public function testItCanSetAndGetTheUriFilter(): void
    {
        $sut = new ResourceConstraints();
        $this->assertNull($sut->getUri());

        $sut2 = $sut->withUri('foo');
        $this->assertNotSame($sut, $sut2);
        $this->assertSame('foo', $sut2->getUri());
        $this->assertNull($sut->getUri());

        $sut3 = $sut2->withUri(null);
        $this->assertNotSame($sut2, $sut3);
        $this->assertNull($sut3->getUri());
        $this->assertSame('foo', $sut2->getUri());
    }

    public function testItCanSetAndGetOwnerFilter(): void
    {
        $sut = new ResourceConstraints();
        $this->assertNull($sut->getOwner());
        $this->assertFalse($sut->isSharedOnly());

        $sut2 = $sut->withOwner('83335934-fc49-4c59-8199-de47c3d03ac5');
        $this->assertNotSame($sut, $sut2);
        $this->assertSame('83335934-fc49-4c59-8199-de47c3d03ac5', $sut2->getOwner());
        $this->assertFalse($sut2->isSharedOnly());
        $this->assertNull($sut->getOwner());

        $sut3 = $sut2->withOwner('83335934-fc49-4c59-8199-de47c3d03ac8', true);
        $this->assertNotSame($sut2, $sut3);
        $this->assertSame('83335934-fc49-4c59-8199-de47c3d03ac8', $sut3->getOwner());
        $this->assertTrue($sut3->isSharedOnly());
        $this->assertSame('83335934-fc49-4c59-8199-de47c3d03ac5', $sut2->getOwner());
        $this->assertFalse($sut2->isSharedOnly());

        $id = new DummyUuid();
        $owner = $this->createStub(User::class);
        $owner->method('getId')->willReturn($id);
        $sut4 = $sut3->withOwner($owner);
        $this->assertNotSame($sut3, $sut4);
        $this->assertSame((string)$id, $sut4->getOwner());
        $this->assertTrue($sut4->isSharedOnly()); // Kept, because not directly set

        $sut5 = $sut4->withOwner(null);
        $this->assertNotSame($sut4, $sut5);
        $this->assertNull($sut5->getOwner());
        $this->assertFalse($sut5->isSharedOnly());
        $this->assertSame((string)$id, $sut4->getOwner());
        $this->assertTrue($sut4->isSharedOnly());

        $id = new DummyUuid();
        $sut6 = $sut5->withOwner($id);
        $this->assertNotSame($sut5, $sut6);
        $this->assertSame((string)$id, $sut6->getOwner());
        $this->assertFalse($sut6->isSharedOnly());
    }

    public function testItCanSetAndGetSharedWithFilter(): void
    {
        $sut = new ResourceConstraints();
        $this->assertNull($sut->getSharedWith());

        $sut2 = $sut->withSharedWith('83335934-fc49-4c59-8199-de47c3d03ac5');
        $this->assertNotSame($sut, $sut2);
        $this->assertSame('83335934-fc49-4c59-8199-de47c3d03ac5', $sut2->getSharedWith());
        $this->assertNull($sut->getSharedWith());

        $id = new DummyUuid();
        $otherUser = $this->createStub(User::class);
        $otherUser->method('getId')->willReturn($id);
        $sut3 = $sut2->withSharedWith($otherUser);
        $this->assertNotSame($sut2, $sut3);
        $this->assertSame((string)$id, $sut3->getSharedWith());
        $this->assertSame('83335934-fc49-4c59-8199-de47c3d03ac5', $sut2->getSharedWith());

        $sut4 = $sut3->withSharedWith(null);
        $this->assertNotSame($sut3, $sut4);
        $this->assertNull($sut4->getSharedWith());
        $this->assertSame((string)$id, $sut3->getSharedWith());
    }

    public function testItCanSetAndGetTypeFilter(): void
    {
        $sut = new ResourceConstraints();
        $this->assertNull($sut->getType());

        $sut2 = $sut->withType('foo');
        $this->assertNotSame($sut, $sut2);
        $this->assertSame('foo', $sut2->getType());
        $this->assertNull($sut->getType());

        $sut3 = $sut2->withType(null);
        $this->assertNotSame($sut2, $sut3);
        $this->assertNull($sut3->getType());
        $this->assertSame('foo', $sut2->getType());
    }

    public function testItCanGetAndSetIdFilter(): void
    {
        $id1 = new DummyUuid(1);
        $id2 = new DummyUuid(2);
        $sut = new ResourceConstraints();
        $this->assertEmpty($sut->getIds());
        $sut2 = $sut->withIds($id1, $id2, $id1);
        $this->assertNotSame($sut, $sut2);
        $this->assertSame([$id1, $id2], $sut2->getIds());
        $this->assertEmpty($sut->getIds());
    }
}
