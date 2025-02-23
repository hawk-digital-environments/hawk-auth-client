<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Roles\Value;


use Hawk\AuthClient\Roles\Value\Role;
use Hawk\AuthClient\Tests\TestUtils\DummyUuid;
use Hawk\AuthClient\Util\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Role::class)]
class RoleTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new Role(new DummyUuid(), 'foo', false, 'desc', []);
        $this->assertInstanceOf(Role::class, $sut);
    }

    public function testItCanReturnGetterValues(): void
    {
        $id = new DummyUuid();
        $sut = new Role($id, 'foo', false, 'desc', ['attr' => 'val']);
        $this->assertSame($id, $sut->getId());
        $this->assertEquals('foo', $sut->getName());
        $this->assertFalse($sut->isClientRole());
        $this->assertEquals('desc', $sut->getDescription());
        $this->assertEquals(['attr' => 'val'], $sut->getAttributes());
        $this->assertEquals('val', $sut->getAttribute('attr'));
        $this->assertNull($sut->getAttribute('not-exist'));
    }

    public function testItCanStringify(): void
    {
        $sut = new Role(new DummyUuid(), 'foo', false, 'desc', []);
        $this->assertEquals('foo', (string)$sut);
    }

    public function testItCanBeJsonEncoded(): void
    {
        $id = new DummyUuid();
        $sut = new Role($id, 'foo', false, 'desc', []);
        $this->assertEquals(
            '{"id":"' . $id . '","name":"foo","isClientRole":false,"description":"desc","attributes":[]}',
            json_encode($sut)
        );
    }

    public function testItCanBeRecreatedFromJson(): void
    {
        $sut = new Role(new Uuid(new DummyUuid()), 'foo', false, 'desc', ['attr' => 'val']);
        $sut2 = Role::fromArray($sut->jsonSerialize());
        $this->assertEquals($sut, $sut2);
    }
}
