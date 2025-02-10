<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Util;


use Hawk\AuthClient\Exception\InvalidUuidException;
use Hawk\AuthClient\Util\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Validator::class)]
#[CoversClass(InvalidUuidException::class)]
class ValidatorTest extends TestCase
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
    }

    #[DataProvider('provideTestItCanDetectUuidsData')]
    public function testItCanDetectUuids(string $uuid, bool $expected): void
    {
        $this->assertEquals($expected, Validator::isUuid($uuid));
    }

    #[DataProvider('provideTestItCanDetectUuidsData')]
    public function testItCanRequireUuidValue(string $uuid, bool $expected): void
    {
        if ($expected) {
            $this->expectNotToPerformAssertions();
        } else {
            $this->expectException(InvalidUuidException::class);
        }
        Validator::requireUuid($uuid);
    }
}
