<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Profiles\Structure\Util;


use Hawk\AuthClient\Keycloak\Value\ConnectionConfig;
use Hawk\AuthClient\Profiles\Structure\Util\ProfilePrefixTrait;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversTrait(ProfilePrefixTrait::class)]
class ProfilePrefixTraitTest extends TestCase
{

    public static function provideTestItCanCheckForFullNamesData(): iterable
    {
        yield 'local known field as name' => ['known-attribute', null, false];
        yield 'local known field as full name' => ['hawk.test-client-id.known-attribute', null, true];
        yield 'local unknown field as name' => ['unknown-attribute', null, false];
        yield 'local unknown field as full name' => ['hawk.test-client-id.unknown-attribute', null, true];
        yield 'global field as name' => ['known-attribute', false, true];
        yield 'global field as full name' => ['hawk.test-client-id.known-attribute', false, true];
        yield 'global known field as name' => ['other-known-attribute', false, true];
        yield 'foreign field as name' => ['known-attribute', 'other-client', false];
        yield 'foreign field as full name' => ['hawk.other-client.known-attribute', 'other-client', true];
        yield 'foreign unknown field as name' => ['unknown-attribute', 'other-client', false];
        yield 'foreign unknown field as full name' => ['hawk.other-client.unknown-attribute', 'other-client', true];
        yield 'field from stringable as name' => [new class {
            public function __toString(): string
            {
                return 'known-attribute';
            }
        }, null, false];
    }

    #[DataProvider('provideTestItCanCheckForFullNamesData')]
    public function testItCanCheckForFullNames(mixed $name, mixed $clientId, bool $expected): void
    {
        $config = $this->createStub(ConnectionConfig::class);
        $config->method('getClientId')->willReturn('test-client-id');

        $sut = new class($config) {
            use ProfilePrefixTrait {
                isFullName as public;
            }

            public function __construct(ConnectionConfig $config)
            {
                $this->config = $config;
            }
        };

        $this->assertEquals($expected, $sut->isFullName($name, $clientId));
    }

    public static function provideTestItCanGetTheFullNameData(): iterable
    {
        yield 'local name is converted to full name' => ['known-attribute', null, 'hawk.test-client-id.known-attribute'];
        yield 'local full name is returned as is' => ['hawk.test-client-id.known-attribute', null, 'hawk.test-client-id.known-attribute'];
        yield 'local unknown name is converted to full name' => ['unknown-attribute', null, 'hawk.test-client-id.unknown-attribute'];
        yield 'local unknown full name is returned as is' => ['hawk.test-client-id.unknown-attribute', null, 'hawk.test-client-id.unknown-attribute'];
        // Not 100% sure about this, but it seems to be the current behavior
        yield 'local full name with other client id is returned as is' => ['hawk.test-client-id.known-attribute', 'other-client', 'hawk.test-client-id.known-attribute'];
        yield 'global name is kept as is' => ['known-attribute', false, 'known-attribute'];
        yield 'full name is kept as is, even in global' => ['hawk.test-client-id.known-attribute', false, 'hawk.test-client-id.known-attribute'];
        yield 'field mit foreign client id is converted to full name' => ['known-attribute', 'other-client', 'hawk.other-client.known-attribute'];
        yield 'field from stringable is converted to full name' => [new class {
            public function __toString(): string
            {
                return 'known-attribute';
            }
        }, null, 'hawk.test-client-id.known-attribute'];
    }

    #[DataProvider('provideTestItCanGetTheFullNameData')]
    public function testItCanGetTheFullName(mixed $name, mixed $clientId, string $expected): void
    {
        $config = $this->createStub(ConnectionConfig::class);
        $config->method('getClientId')->willReturn('test-client-id');

        $sut = new class($config) {
            use ProfilePrefixTrait {
                getFullName as public;
            }

            public function __construct(ConnectionConfig $config)
            {
                $this->config = $config;
            }
        };

        $this->assertEquals($expected, $sut->getFullName($name, $clientId));
    }

    public static function getTestItCanGetTheLocalNameData(): iterable
    {
        yield 'local name is kept as is' => ['known-attribute', null, 'known-attribute'];
        yield 'local full name is converted to name' => ['hawk.test-client-id.known-attribute', null, 'known-attribute'];
        yield 'local unknown name is kept as is' => ['unknown-attribute', null, 'unknown-attribute'];
        yield 'local unknown full name is converted to name' => ['hawk.test-client-id.unknown-attribute', null, 'unknown-attribute'];
        yield 'global name is kept as is' => ['known-attribute', false, 'known-attribute'];
        yield 'global full name is kept as is' => ['hawk.test-client-id.known-attribute', false, 'hawk.test-client-id.known-attribute'];
        yield 'foreign full name is converted to name' => ['hawk.other-client.known-attribute', 'other-client', 'known-attribute'];
        yield 'foreign full name with local client is kept as is' => ['hawk.other-client.known-attribute', null, 'hawk.other-client.known-attribute'];
        yield 'foreign unknown full name is converted to name' => ['hawk.other-client.unknown-attribute', 'other-client', 'unknown-attribute'];
        yield 'field from stringable is converted to name' => [new class {
            public function __toString(): string
            {
                return 'known-attribute';
            }
        }, null, 'known-attribute'];
    }

    #[DataProvider('getTestItCanGetTheLocalNameData')]
    public function testItCanGetTheLocalName(mixed $fullName, mixed $clientId, string $expected): void
    {
        $config = $this->createStub(ConnectionConfig::class);
        $config->method('getClientId')->willReturn('test-client-id');

        $sut = new class($config) {
            use ProfilePrefixTrait {
                getLocalName as public;
            }

            public function __construct(ConnectionConfig $config)
            {
                $this->config = $config;
            }
        };

        $this->assertEquals($expected, $sut->getLocalName($fullName, $clientId));
    }

    public static function provideTestItCanCheckIfNameBelongsToClientIdData(): iterable
    {
        yield 'local name belongs to local client' => ['hawk.test-client-id.known-attribute', null, true];
        yield 'local name does not belong to other client' => ['hawk.test-client-id.known-attribute', 'other-client', false];
        yield 'local name does not belong to global' => ['hawk.test-client-id.known-attribute', false, false];
        yield 'global name belongs to global' => ['known-attribute', false, true];
        yield 'global name does not belong to local client' => ['known-attribute', null, false];
        yield 'global name does not belong to other client' => ['known-attribute', 'other-client', false];
        yield 'foreign name belongs to foreign client' => ['hawk.other-client.known-attribute', 'other-client', true];
        yield 'foreign name does not belong to local client' => ['hawk.other-client.known-attribute', null, false];
        yield 'foreign name does not belong to global' => ['hawk.other-client.known-attribute', false, false];
    }

    #[DataProvider('provideTestItCanCheckIfNameBelongsToClientIdData')]
    public function testItCanCheckIfNameBelongsToClientId(mixed $fullName, mixed $clientId, bool $expected): void
    {
        $config = $this->createStub(ConnectionConfig::class);
        $config->method('getClientId')->willReturn('test-client-id');

        $sut = new class($config) {
            use ProfilePrefixTrait {
                belongsTo as public;
            }

            public function __construct(ConnectionConfig $config)
            {
                $this->config = $config;
            }
        };

        $this->assertEquals($expected, $sut->belongsTo($fullName, $clientId));
    }

    public static function getTestItCanGetThePrefixData(): iterable
    {
        yield 'global' => [false, ''];
        yield 'local' => [null, 'hawk.test-client-id.'];
        yield 'foreign' => ['other-client', 'hawk.other-client.'];
        yield 'stringable client id' => [new class {
            public function __toString(): string
            {
                return 'other-client';
            }
        }, 'hawk.other-client.'];
    }

    #[DataProvider('getTestItCanGetThePrefixData')]
    public function testItCanGetThePrefix(mixed $clientId, string $expected): void
    {
        $config = $this->createStub(ConnectionConfig::class);
        $config->method('getClientId')->willReturn('test-client-id');

        $sut = new class($config) {
            use ProfilePrefixTrait {
                getPrefix as public;
            }

            public function __construct(ConnectionConfig $config)
            {
                $this->config = $config;
            }
        };

        $this->assertSame($expected, $sut->getPrefix($clientId));
    }
}
