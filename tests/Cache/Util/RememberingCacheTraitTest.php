<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Cache\Util;

use Hawk\AuthClient\Cache\Util\RememberingCacheTrait;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\TestCase;

#[CoversTrait(RememberingCacheTrait::class)]
class RememberingCacheTraitTest extends TestCase
{
    public function testItCanRemember(): void
    {
        $sut = $this->createSut();
        $sut->onGet = static fn() => 'bar';
        $sut->onSet = fn() => $this->fail('Should not set');
        $res = $sut->remember('foo', fn() => $this->fail('Should not fetch a value'));
        $this->assertEquals('bar', $res);
    }

    public function testItCanRememberAndDeserializeAValue(): void
    {
        $sut = $this->createSut();
        $sut->onGet = static fn() => 'bar';
        $res = $sut->remember(
            'foo',
            fn() => $this->fail('Should not fetch a value'),
            cacheToValue: fn($value) => $value . '-deserialized'
        );
        $this->assertEquals('bar-deserialized', $res);
    }

    public function testItCanGenerateAValueAndSetIt(): void
    {
        $sut = $this->createSut();
        $sut->onSet = function ($key, $value, $ttl) {
            $this->assertEquals('foo', $key);
            $this->assertEquals('bar', $value);
            $this->assertNull($ttl);
        };

        $res = $sut->remember(
            'foo',
            valueGenerator: fn() => 'bar'
        );
        $this->assertEquals('bar', $res);
    }

    public function testItDoesNotPassGeneratedValuesThroughCacheToValue(): void
    {
        $res = $this->createSut()->remember(
            'foo',
            valueGenerator: fn() => 'bar',
            cacheToValue: fn() => $this->fail('Should not pass generated value')
        );
        
        $this->assertEquals('bar', $res);
    }

    public function testItCanGenerateAValueAndSerializeItToCache(): void
    {
        $sut = $this->createSut();
        $sut->onSet = function ($key, $value, $ttl) {
            $this->assertEquals('foo', $key);
            $this->assertEquals('bar-serialized', $value);
            $this->assertNull($ttl);
        };

        $res = $sut->remember(
            'foo',
            valueGenerator: fn() => 'bar',
            valueToCache: fn($value) => $value . '-serialized'
        );
        $this->assertEquals('bar', $res);
    }

    public function testItCanGenerateAValueAndSetItWithTtl(): void
    {
        $sut = $this->createSut();
        $sut->onSet = function ($key, $value, $ttl) {
            $this->assertEquals('foo', $key);
            $this->assertEquals('bar', $value);
            $this->assertEquals(42, $ttl);
        };
        $res = $sut->remember(
            'foo',
            valueGenerator: fn() => 'bar',
            ttl: 42
        );
        $this->assertEquals('bar', $res);
    }

    public function testItCanGenerateAValueAndSetItWithTtlCallable(): void
    {
        $sut = $this->createSut();
        $sut->onSet = function ($key, $value, $ttl) {
            $this->assertEquals('foo', $key);
            $this->assertEquals('bar', $value);
            $this->assertEquals(42, $ttl);
        };
        $res = $sut->remember(
            'foo',
            valueGenerator: fn() => 'bar',
            ttl: function ($value) {
                $this->assertEquals('bar', $value);
                return 42;
            }
        );
        $this->assertEquals('bar', $res);
    }

    protected function createSut()
    {
        return new class {
            use RememberingCacheTrait;

            public $onGet;
            public $onSet;

            public function get(string $key): mixed
            {
                if (is_callable($this->onGet)) {
                    return ($this->onGet)($key);
                }
                return null;
            }

            public function set(string $key, mixed $value, ?int $ttl = null): void
            {
                if (is_callable($this->onSet)) {
                    ($this->onSet)($key, $value, $ttl);
                }
            }
        };
    }
}
