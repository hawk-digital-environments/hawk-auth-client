<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Keycloak\Value;


use Hawk\AuthClient\Exception\InvalidInternalKeycloakUrlException;
use Hawk\AuthClient\Exception\InvalidPublicKeycloakUrlException;
use Hawk\AuthClient\Exception\InvalidRedirectUrlException;
use Hawk\AuthClient\Keycloak\Value\ConnectionConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConnectionConfig::class)]
#[CoversClass(InvalidRedirectUrlException::class)]
#[CoversClass(InvalidPublicKeycloakUrlException::class)]
#[CoversClass(InvalidInternalKeycloakUrlException::class)]
class ConnectionConfigTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new ConnectionConfig(
            redirectUrl: 'http://example.com',
            redirectUrlAfterLogout: null,
            publicKeycloakUrl: 'http://example.com',
            internalKeycloakUrl: null,
            clientId: 'foo',
            clientSecret: 'bar',
            realm: 'baz'
        );
        $this->assertInstanceOf(ConnectionConfig::class, $sut);
    }

    public function testItFailsToConstructWithInvalidRedirectUrl(): void
    {
        $this->expectException(InvalidRedirectUrlException::class);
        new ConnectionConfig(
            redirectUrl: 'invalid-url',
            redirectUrlAfterLogout: null,
            publicKeycloakUrl: 'http://example.com',
            internalKeycloakUrl: null,
            clientId: 'foo',
            clientSecret: 'bar',
            realm: 'baz'
        );
    }

    public function testItFailsToConstructWithInvalidPublicKeycloakUrl(): void
    {
        $this->expectException(InvalidPublicKeycloakUrlException::class);
        new ConnectionConfig(
            redirectUrl: 'http://example.com',
            redirectUrlAfterLogout: null,
            publicKeycloakUrl: 'invalid-url',
            internalKeycloakUrl: null,
            clientId: 'foo',
            clientSecret: 'bar',
            realm: 'baz'
        );
    }

    public function testItFailsToConstructWithInvalidInternalKeycloakUrl(): void
    {
        $this->expectException(InvalidInternalKeycloakUrlException::class);
        new ConnectionConfig(
            redirectUrl: 'http://example.com',
            redirectUrlAfterLogout: null,
            publicKeycloakUrl: 'http://example.com',
            internalKeycloakUrl: 'invalid-url',
            clientId: 'foo',
            clientSecret: 'bar',
            realm: 'baz'
        );
    }

    public function testItGetsConfiguredValues(): void
    {
        $sut = new ConnectionConfig(
            redirectUrl: 'http://example.com',
            redirectUrlAfterLogout: 'http://example.com/logout',
            publicKeycloakUrl: 'http://example.com',
            internalKeycloakUrl: 'http://example.com/internal',
            clientId: 'foo',
            clientSecret: 'bar',
            realm: 'baz'
        );
        $this->assertSame('http://example.com', $sut->getRedirectUrl());
        $this->assertSame('http://example.com/logout', $sut->getRedirectUrlAfterLogout());
        $this->assertSame('http://example.com', $sut->getPublicKeycloakUrl());
        $this->assertSame('http://example.com/internal', $sut->getInternalKeycloakUrl());
        $this->assertSame('foo', $sut->getClientId());
        $this->assertSame('bar', $sut->getClientSecret());
        $this->assertSame('baz', $sut->getRealm());
        $this->assertSame('3fdcab57761ee45892c604e6de7f1634a7d68e5eadca41c0b157a5c4ee6d3d59', $sut->getHash());
    }

    public function testTheDebugInfoDoesNotIncludeClientIdOrSecret(): void
    {
        $sut = new ConnectionConfig(
            redirectUrl: 'http://example.com',
            redirectUrlAfterLogout: 'http://example.com/logout',
            publicKeycloakUrl: 'http://example.com',
            internalKeycloakUrl: 'http://example.com/internal',
            clientId: 'foo',
            clientSecret: 'bar',
            realm: 'baz'
        );

        $this->assertStringNotContainsString('foo', print_r($sut, true));
        $this->assertStringNotContainsString('bar', print_r($sut, true));
    }
}
