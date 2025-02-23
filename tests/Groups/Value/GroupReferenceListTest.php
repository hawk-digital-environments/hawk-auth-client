<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Groups\Value;


use Hawk\AuthClient\Groups\Value\Group;
use Hawk\AuthClient\Groups\Value\GroupList;
use Hawk\AuthClient\Groups\Value\GroupReference;
use Hawk\AuthClient\Groups\Value\GroupReferenceList;
use Hawk\AuthClient\Tests\TestUtils\DummyUuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(GroupReferenceList::class)]
class GroupReferenceListTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new GroupReferenceList();
        $this->assertInstanceOf(GroupReferenceList::class, $sut);
    }

    public static function provideTestItCallsTheGetterFacadesCorrectlyData(): iterable
    {
        yield 'has Any, true' => ['hasAny', ['foo'], false, true];
        yield 'has Any, false' => ['hasAny', ['foo', 'bar', new GroupReference('baz')], false, false];
        yield 'has Any or has Child of Any, true' => ['hasAnyOrHasChildOfAny', ['foo'], true, true];
        yield 'has Any or has Child of Any, false' => ['hasAnyOrHasChildOfAny', ['foo', new GroupReference('baz')], true, false];
    }

    #[DataProvider('provideTestItCallsTheGetterFacadesCorrectlyData')]
    public function testItCallsTheGetterFacadesCorrectly(string $method, array $args, bool $includeParents, bool $expectedResult): void
    {
        $sut = $this->createPartialMock(GroupReferenceList::class, ['hasAnyInternal']);
        $sut->expects($this->once())
            ->method('hasAnyInternal')
            ->with($args, $includeParents)
            ->willReturn($expectedResult);

        $this->assertEquals($expectedResult, $sut->{$method}(...$args));
    }

    public static function provideTestItCanCheckIfItHasAnyData(): iterable
    {
        yield 'hasAny, nothing' => ['hasAny', [], false];
        yield 'hasAnyOrHasChildOfAny, nothing' => ['hasAnyOrHasChildOfAny', [], false];
        yield 'hasAny, fuzzy path compare, found' => ['hasAny', ['faz'], true];
        yield 'hasAny, one string, found' => ['hasAny', ['foo'], true];
        yield 'hasAny, one string, not found' => ['hasAny', ['raz'], false];
        yield 'hasAny, one uuid string, found' => ['hasAny', [(string)new DummyUuid(1)], true];
        yield 'hasAny, one uuid, found' => ['hasAny', [new DummyUuid(1)], true];
        yield 'hasAny, GroupReference, found' => ['hasAny', [new GroupReference('foo')], true];
        yield 'hasAny, GroupReference, not found' => ['hasAny', [new GroupReference('raz')], false];
        yield 'hasAny, Group, found' => ['hasAny', [new Group(new DummyUuid(1), 'name', '/name', new GroupList())], true];
        yield 'hasAny, Group, not found' => ['hasAny', [new Group(new DummyUuid(3), 'name', '/name', new GroupList())], false];
        yield 'hasAny, multiple, all found' => ['hasAny', ['foo', new GroupReference('baz'), new Group(new DummyUuid(1), 'name', '/name', new GroupList())], true];
        yield 'hasAny, multiple, only one found' => ['hasAny', ['/raz', new GroupReference('baz'), new Group(new DummyUuid(2), 'name', '/name', new GroupList())], true];
        yield 'hasAnyOrHasChildOfAny, parent path, found' => ['hasAnyOrHasChildOfAny', ['/baz'], true];
        yield 'hasAnyOrHasChildOfAny, parent path, not found' => ['hasAnyOrHasChildOfAny', ['/raz/bar'], false];
        yield 'hasAnyOrHasChildOfAny, exact path, found' => ['hasAnyOrHasChildOfAny', ['/baz'], true];
        yield 'hasAny, group exact path match, found' => ['hasAny', [new Group(new DummyUuid(1), 'name', '/faz', new GroupList())], true];
        yield 'hasAnyOrHasChildOfAny, parent path match, found' => ['hasAnyOrHasChildOfAny', ['/baz'], true];
        yield 'hasAny, group name match, found' => ['hasAny', [new Group(new DummyUuid(1), 'bar', '/name', new GroupList())], true];
    }

    #[DataProvider('provideTestItCanCheckIfItHasAnyData')]
    public function testItCanCheckIfItHasAny(string $method, array $args, bool $expectedResult): void
    {
        $ref1 = new GroupReference('foo');
        $ref2 = new GroupReference('bar');
        $ref3 = new GroupReference('/baz/bar');
        $ref4 = new GroupReference('/faz');
        $ref5 = new GroupReference((string)new DummyUuid(1));
        $ref6 = new GroupReference((string)new DummyUuid(2));

        $sut = new GroupReferenceList($ref1, $ref2, $ref3, $ref4, $ref5, $ref6);

        $this->assertEquals($expectedResult, $sut->{$method}(...$args));
    }

    public function testItCanBeIterated(): void
    {
        $ref1 = new GroupReference('foo');
        $ref2 = new GroupReference('bar');
        $ref3 = new GroupReference('baz');
        $ref4 = new GroupReference('faz');
        $ref5 = new GroupReference((string)new DummyUuid(1));
        $ref6 = new GroupReference((string)new DummyUuid(2));

        $sut = new GroupReferenceList($ref1, $ref2, $ref3, $ref4, $ref5, $ref6);

        $this->assertEquals([$ref1, $ref2, $ref3, $ref4, $ref5, $ref6], iterator_to_array($sut));
    }

    public function testItCanBeCreatedFromScalarList(): void
    {
        $sut = GroupReferenceList::fromScalarList('foo', 'bar', 'baz');
        $this->assertEquals(
            new GroupReferenceList(
                new GroupReference('foo'),
                new GroupReference('bar'),
                new GroupReference('baz')
            ),
            $sut
        );
    }

}
