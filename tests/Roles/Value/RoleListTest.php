<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Roles\Value;


use Hawk\AuthClient\Roles\Value\Role;
use Hawk\AuthClient\Roles\Value\RoleList;
use Hawk\AuthClient\Tests\TestUtils\DummyUuid;
use Hawk\AuthClient\Util\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RoleList::class)]
class RoleListTest extends TestCase
{
    public function testItCanConstruct(): void
    {
        $sut = new RoleList();
        $this->assertInstanceOf(RoleList::class, $sut);
    }

    public function testItCanIterate(): void
    {
        $role1 = $this->createStub(Role::class);
        $role2 = $this->createStub(Role::class);
        $sut = new RoleList($role1, $role2);

        $this->assertEquals([$role1, $role2], iterator_to_array($sut, false));
    }

    public function testItCanBeCreatedFromScalarList(): void
    {
        $role1 = new Role(new Uuid(new DummyUuid(1)), 'foo', false, 'desc', []);
        $role2 = new Role(new Uuid(new DummyUuid(2)), 'bar', false, 'desc', []);

        $sut = new RoleList($role1, $role2);
        $sut2 = RoleList::fromScalarList(...json_decode(json_encode($sut), true));
        $this->assertEquals($sut, $sut2);
    }

}
