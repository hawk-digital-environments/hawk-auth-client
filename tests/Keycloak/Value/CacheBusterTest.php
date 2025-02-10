<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Keycloak\Value;


use Hawk\AuthClient\Keycloak\Value\CacheBuster;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CacheBuster::class)]
class CacheBusterTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new CacheBuster('cacheBuster');
        $this->assertInstanceOf(CacheBuster::class, $sut);
    }

    public function testItCanBeStringified(): void
    {
        $sut = new CacheBuster('cacheBuster');
        $this->assertEquals('cacheBuster', (string)$sut);
    }
}
