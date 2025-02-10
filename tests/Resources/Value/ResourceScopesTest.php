<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Resources\Value;


use Hawk\AuthClient\Resources\Value\ResourceScopes;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ResourceScopes::class)]
class ResourceScopesTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new ResourceScopes();
        $this->assertInstanceOf(ResourceScopes::class, $sut);
    }

    public function testItCanCheckIfItHasAny(): void
    {
        $sut = new ResourceScopes('foo', 'bar');
        $this->assertTrue($sut->hasAny('foo'));
        $this->assertTrue($sut->hasAny('bar'));
        $this->assertFalse($sut->hasAny('baz'));
        $this->assertTrue($sut->hasAny('foo', 'bar'));
        $this->assertTrue($sut->hasAny('foo', 'baz'));
        $this->assertFalse($sut->hasAny('baz', 'qux'));
        $this->assertTrue($sut->hasAny());
    }

    public function testItCanCheckIfItHasAll(): void
    {
        $sut = new ResourceScopes('foo', 'bar');
        $this->assertTrue($sut->hasAll('foo'));
        $this->assertTrue($sut->hasAll('bar'));
        $this->assertFalse($sut->hasAll('baz'));
        $this->assertTrue($sut->hasAll('foo', 'bar'));
        $this->assertFalse($sut->hasAll('foo', 'baz'));
        $this->assertFalse($sut->hasAll('baz', 'qux'));
        $this->assertFalse($sut->hasAll());
    }

    public function testItCanBeIterated(): void
    {
        foreach (new ResourceScopes() as $_) {
            $this->fail('Should not iterate');
        }

        foreach (new ResourceScopes('foo', 'bar') as $i => $scope) {
            if ($i === 0) {
                $this->assertEquals('foo', $scope);
            } else {
                $this->assertEquals('bar', $scope);
            }
        }
    }

    public function testItCanBeJsonSerialized(): void
    {
        $this->assertJsonStringEqualsJsonString(
            '["foo","bar"]',
            json_encode(new ResourceScopes('foo', 'bar'))
        );
    }

}
