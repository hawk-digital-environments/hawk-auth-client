<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Roles\Value;


use Hawk\AuthClient\Roles\Value\Role;
use Hawk\AuthClient\Roles\Value\RoleReference;
use Hawk\AuthClient\Roles\Value\RoleReferenceList;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(RoleReferenceList::class)]
class RoleReferenceListTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new RoleReferenceList();
        $this->assertInstanceOf(RoleReferenceList::class, $sut);
    }

    public static function provideTestItCanCheckIfItHasAnyData(): iterable
    {
        yield 'nothing' => [[], false];
        yield 'one string' => [['foo'], true];
        yield 'one uuid' => [['f47ac10b-58cc-4372-a567-0e02b2c3d001'], true];
        yield 'one RoleReference' => [[new RoleReference('foo')], true];
        yield 'one RoleReference uuid' => [[new RoleReference('f47ac10b-58cc-4372-a567-0e02b2c3d001')], true];
        yield 'multiple RoleReferences' => [[new RoleReference('foo'), new RoleReference('bar')], true];
        yield 'one Role' => [[new Role('f47ac10b-58cc-4372-a567-0e02b2c3d001', 'foo', false, 'desc', [])], true];
        yield 'multiple Roles' => [[
            new Role('f47ac10b-58cc-4372-a567-0e02b2c3d002', 'bar', false, 'desc', []),
            new Role('f47ac10b-58cc-4372-a567-0e02b2c3d001', 'foo', false, 'desc', [])
        ], true];
        yield 'missing string' => [['bar'], false];
        yield 'missing uuid' => [['f47ac10b-58cc-4372-a567-0e02b2c3d002'], false];
        yield 'missing RoleReference' => [[new RoleReference('bar')], false];
        yield 'missing RoleReference uuid' => [[new RoleReference('f47ac10b-58cc-4372-a567-0e02b2c3d002')], false];
        yield 'missing multiple RoleReferences' => [[new RoleReference('faz'), new RoleReference('baz')], false];
        yield 'mixed' => [[
            'foo',
            new RoleReference('bar'),
            new Role('f47ac10b-58cc-4372-a567-0e02b2c3d001', 'foo', false, 'desc', []),
            new Role('f47ac10b-58cc-4372-a567-0e02b2c3d002', 'bar', false, 'desc', [])
        ], true];
        yield 'role by id' => [[new Role('f47ac10b-58cc-4372-a567-0e02b2c3d003', 'fratz', false, 'desc', [])], true];
    }

    #[DataProvider('provideTestItCanCheckIfItHasAnyData')]
    public function testItCanCheckIfItHasAny(array $roles, bool $expected): void
    {
        $sut = new RoleReferenceList(
            new RoleReference('foo'),
            new RoleReference('f47ac10b-58cc-4372-a567-0e02b2c3d001'),
            new RoleReference('f47ac10b-58cc-4372-a567-0e02b2c3d003'),
        );

        $this->assertEquals($expected, $sut->hasAny(...$roles));
    }

    public function testItCanIterate(): void
    {
        $ref1 = new RoleReference('foo');
        $ref2 = new RoleReference('bar');
        $ref3 = new RoleReference('baz');

        $sut = new RoleReferenceList($ref1, $ref2, $ref3);

        $this->assertEquals([$ref1, $ref2, $ref3], iterator_to_array($sut, false));
    }

    public function testItCanBeCreatedFromScalarList(): void
    {
        $sut = RoleReferenceList::fromScalarList('foo', 'bar', 'baz');
        $this->assertEquals([
            new RoleReference('foo'),
            new RoleReference('bar'),
            new RoleReference('baz'),
        ], iterator_to_array($sut, false));
    }

}
