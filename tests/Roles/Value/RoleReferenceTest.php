<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Roles\Value;


use Hawk\AuthClient\Roles\RoleReferenceTypeEnum;
use Hawk\AuthClient\Roles\Value\RoleReference;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(RoleReference::class)]
class RoleReferenceTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new RoleReference('foo');
        $this->assertInstanceOf(RoleReference::class, $sut);
    }

    public function testItCanStringify(): void
    {
        $sut = new RoleReference('foo');
        $this->assertEquals('foo', (string)$sut);
    }

    public function testItCanBeJsonEncoded(): void
    {
        $sut = new RoleReference('foo');
        $this->assertEquals('"foo"', json_encode($sut));
    }

    public static function provideTestItCanReturnTheCorrectTypeData(): iterable
    {
        yield 'valid uuid' => ['f47ac10b-58cc-4372-a567-0e02b2c3d001', RoleReferenceTypeEnum::ID];
        yield 'name' => ['foo', RoleReferenceTypeEnum::NAME];
        yield 'invalid uuid' => ['f47ac10b-58cc-4372-a7-0e02b2c3d001', RoleReferenceTypeEnum::NAME];
    }

    #[DataProvider('provideTestItCanReturnTheCorrectTypeData')]
    public function testItCanReturnTheCorrectType(string $value, RoleReferenceTypeEnum $type): void
    {
        $sut = new RoleReference($value);
        $this->assertEquals($type, $sut->getType());
    }
}
