<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Profiles\Value;


use Hawk\AuthClient\Profiles\Value\ProfileFieldValidationError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProfileFieldValidationError::class)]
class ProfileFieldValidationErrorTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new ProfileFieldValidationError('test-field', 'test-message', []);
        $this->assertInstanceOf(ProfileFieldValidationError::class, $sut);
    }

    public function testItCanGetValues(): void
    {
        $sut = new ProfileFieldValidationError('test-field', 'test-message', ['test-context' => 'test-value']);
        $this->assertEquals('test-field', $sut->getField());
        $this->assertEquals('test-message', $sut->getMessage());
        $this->assertEquals(['test-context' => 'test-value'], $sut->getParams());
    }

}
