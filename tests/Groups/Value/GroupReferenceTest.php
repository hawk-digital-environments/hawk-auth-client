<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Groups\Value;


use Hawk\AuthClient\Groups\GroupReferenceTypeEnum;
use Hawk\AuthClient\Groups\Value\GroupReference;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(GroupReference::class)]
class GroupReferenceTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new GroupReference('foo');
        $this->assertInstanceOf(GroupReference::class, $sut);
    }

    public function testItCanStringify(): void
    {
        $sut = new GroupReference('/foo');
        $this->assertEquals('/foo', (string)$sut);
    }

    public function testItCanBeJsonEncoded(): void
    {
        $sut = new GroupReference('/foo');
        $this->assertEquals('"\/foo"', json_encode($sut));
    }

    public static function provideTestItCanReturnTheCorrectTypeData(): iterable
    {
        yield 'valid uuid' => ['f47ac10b-58cc-4372-a567-0e02b2c3d001', GroupReferenceTypeEnum::ID];
        yield 'path' => ['/foo', GroupReferenceTypeEnum::PATH];
        yield 'invalid uuid' => ['f47ac10b-58cc-4372-a7-0e02b2c3d001', GroupReferenceTypeEnum::NAME];
        yield 'name' => ['foo', GroupReferenceTypeEnum::NAME];
    }

    #[DataProvider('provideTestItCanReturnTheCorrectTypeData')]
    public function testItCanReturnTheCorrectType(string $value, GroupReferenceTypeEnum $type): void
    {
        $sut = new GroupReference($value);
        $this->assertEquals($type, $sut->getType());
    }

}
