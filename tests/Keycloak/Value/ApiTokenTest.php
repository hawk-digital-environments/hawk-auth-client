<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Keycloak\Value;


use Hawk\AuthClient\Keycloak\Value\ApiToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ApiToken::class)]
class ApiTokenTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new ApiToken('token', new \DateTimeImmutable('2021-01-01T00:00:00Z'));
        $this->assertInstanceOf(ApiToken::class, $sut);
    }

    public function testItChecksIfItIsExpired(): void
    {
        $now = new \DateTimeImmutable('2021-01-01T00:00:00Z');
        $future = new \DateTimeImmutable('2021-01-01T00:00:01Z');
        $past = new \DateTimeImmutable('2020-12-31T23:59:59Z');

        $sut = new ApiToken('token', $now);
        $this->assertTrue($sut->isExpired($future));
        $this->assertTrue($sut->isExpired($now));
        $this->assertFalse($sut->isExpired($past));
    }

    public function testItGetsExpiresAt(): void
    {
        $expiredAt = new \DateTimeImmutable();
        $sut = new ApiToken('token', $expiredAt);
        $this->assertSame($expiredAt, $sut->getExpiresAt());
    }

    public function testItCanBeJsonEncoded(): void
    {
        $sut = new ApiToken('token', new \DateTimeImmutable('2021-01-01T00:00:00Z'));
        $this->assertEquals('{"token":"token","expiresAt":"2021-01-01T00:00:00+00:00"}', json_encode($sut));
    }

    public function testItCanBeStringified(): void
    {
        $sut = new ApiToken('token', new \DateTimeImmutable('2021-01-01T00:00:00Z'));
        $this->assertEquals('token', (string)$sut);
    }

    public function testItCanBeCreatedFromArray(): void
    {
        $sut = new ApiToken('token', new \DateTimeImmutable('2021-01-01T00:00:00Z'));
        $data = $sut->jsonSerialize();
        $sut2 = ApiToken::fromArray($data);
        $this->assertEquals($sut, $sut2);
    }
}
