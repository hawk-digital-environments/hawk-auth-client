<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Util;


use Hawk\AuthClient\Exception\InvalidUuidException;
use Hawk\AuthClient\Util\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Uuid::class)]
#[CoversClass(InvalidUuidException::class)]
class UuidTest extends TestCase
{
    public static function provideTestItCanDetectUuidsData(): iterable
    {
        // Valid UUIDs
        yield 'valid uuid v4' => ['f47ac10b-58cc-4372-a567-0e02b2c3d479', true];
        yield 'uuid uppercase' => ['F47AC10B-58CC-4372-A567-0E02B2C3D479', true];

        // Invalid UUIDs
        yield 'uuid without dashes' => ['f47ac10b58cc4372a5670e02b2c3d479', false];
        yield 'empty string' => ['', false];
        yield 'null string' => ['null', false];
        yield 'incomplete uuid' => ['f47ac10b-58cc-4372-a567', false];
        yield 'too long uuid' => ['f47ac10b-58cc-4372-a567-0e02b2c3d4790', false];
        yield 'invalid characters' => ['g47ac10b-58cc-4372-a567-0e02b2c3d479', false];
        yield 'wrong format' => ['f47ac10b58cc-4372-a567-0e02b2c3d479', false];
        yield 'missing sections' => ['f47ac10b-58cc--a567-0e02b2c3d479', false];
        yield 'special characters' => ['f47ac10b-58cc-4372-a567-0e02b2c3d47@', false];
        yield 'only numbers' => ['12345678-1234-1234-1234-123456789012', false];
        yield 'only letters' => ['aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa', false];
        yield 'with spaces' => ['f47ac10b 58cc 4372 a567 0e02b2c3d479', false];

        // Edge Cases
        yield 'all zeros' => ['00000000-0000-0000-0000-000000000000', true];
        yield 'all fs' => ['ffffffff-ffff-ffff-ffff-ffffffffffff', true];
        yield 'mixed case' => ['F47ac10B-58cC-4372-A567-0e02B2c3d479', true];

        // Existing objects
        yield 'stringable' => [new class {
            public function __toString(): string
            {
                return 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
            }
        }, true];
        yield 'non uuid stringable' => [
            new class {
                public function __toString(): string
                {
                    return 'not-a-uuid';
                }
            },
            false
        ];
        yield 'existing uuid' => [
            new Uuid('f47ac10b-58cc-4372-a567-0e02b2c3d479'),
            true
        ];
    }

    #[DataProvider('provideTestItCanDetectUuidsData')]
    public function testItCanDetectUuids(mixed $uuid, bool $expected): void
    {
        $this->assertEquals($expected, Uuid::isValid($uuid));
    }

    #[DataProvider('provideTestItCanDetectUuidsData')]
    public function testItCanConstructUuids(mixed $uuid, bool $expected): void
    {
        if ($expected) {
            $this->expectNotToPerformAssertions();
        } else {
            $this->expectException(InvalidUuidException::class);
        }
        new Uuid($uuid);
    }

    public function testItCanBeJsonSerialized(): void
    {
        $uuid = new Uuid('f47ac10b-58cc-4372-a567-0e02b2c3d479');
        $this->assertEquals('"f47ac10b-58cc-4372-a567-0e02b2c3d479"', json_encode($uuid));
    }

    #[DataProvider('provideTestItCanDetectUuidsData')]
    public function testItCanCreateAnInstanceFromScalar(mixed $uuid, bool $expected): void
    {
        if (!$expected) {
            $this->expectException(InvalidUuidException::class);
        }
        $sut = Uuid::fromOne($uuid);
        $this->assertInstanceOf(Uuid::class, $sut);
        $this->assertEquals($uuid, (string)$sut);
    }

    public static function provideTestItCanCreateMultipleInstancesFromScalarData(): iterable
    {
        yield 'single uuid' => [['f47ac10b-58cc-4372-a567-0e02b2c3d479'], ['f47ac10b-58cc-4372-a567-0e02b2c3d479']];
        yield 'mixed uuids' => [
            [
                'f47ac10b-58cc-4372-a567-0e02b2c3d479',
                new Uuid('f47ac10b-58cc-4372-a567-0e02b2c3d472'),
                new class implements \Stringable {
                    public function __toString(): string
                    {
                        return 'f47ac10b-58cc-4372-a567-0e02b2c3d473';
                    }
                }
            ],
            [
                'f47ac10b-58cc-4372-a567-0e02b2c3d479',
                'f47ac10b-58cc-4372-a567-0e02b2c3d472',
                'f47ac10b-58cc-4372-a567-0e02b2c3d473'
            ]
        ];
        yield 'uuid deduplication' => [
            [
                'f47ac10b-58cc-4372-a567-0e02b2c3d479',
                'f47ac10b-58cc-4372-a567-0e02b2c3d479',
                'f47ac10b-58cc-4372-a567-0e02b2c3d479'
            ],
            ['f47ac10b-58cc-4372-a567-0e02b2c3d479']
        ];
        yield 'empty array' => [[], []];
        yield 'invalid data' => [['not-auuid'], null];
    }

    #[DataProvider('provideTestItCanCreateMultipleInstancesFromScalarData')]
    public function testItCanCreateMultipleInstancesFromScalar(iterable $data, array|null $expected): void
    {
        if ($expected === null) {
            $this->expectException(InvalidUuidException::class);
        }

        $result = Uuid::fromList(...$data);

        if ($expected !== null) {
            $this->assertEquals($expected, array_map('strval', $result));
        }
    }
}
