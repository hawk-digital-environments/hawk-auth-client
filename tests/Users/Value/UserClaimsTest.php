<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Users\Value;


use Hawk\AuthClient\Users\Value\UserClaims;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UserClaims::class)]
class UserClaimsTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new UserClaims([]);
        $this->assertInstanceOf(UserClaims::class, $sut);
    }

    public function testItCanCheckIfKeysExist(): void
    {
        $sut = new UserClaims(['foo' => 'bar']);
        $this->assertTrue($sut->has('foo'));
        $this->assertFalse($sut->has('bar'));
    }

    public function testItCanRetrieveValues(): void
    {
        $sut = new UserClaims(['foo' => 'bar']);
        $this->assertSame('bar', $sut->get('foo'));
        $this->assertNull($sut->get('bar'));
    }

    public function testItCanReturnTheKeys(): void
    {
        $sut = new UserClaims(['foo' => 'bar']);
        $this->assertSame(['foo'], $sut->getKeys());
    }

    public function testItCanBeIterated(): void
    {
        $sut = new UserClaims(['foo' => 'bar', 'bar' => 'baz']);
        $this->assertSame(['bar', 'baz'], iterator_to_array($sut, false));
    }

    public function testItCanBeJsonEncoded(): void
    {
        $sut = new UserClaims(['foo' => 'bar']);
        $this->assertSame('{"foo":"bar"}', json_encode($sut));
    }
}
