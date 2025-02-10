<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Keycloak\Value;


use Hawk\AuthClient\Keycloak\Value\ClientUuid;
use Hawk\AuthClient\Keycloak\Value\ConnectionInfo;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConnectionInfo::class)]
class ConnectionInfoTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new ConnectionInfo('foo', 'bar', 'baz', new ClientUuid('f47ac10b-58cc-4372-a567-0e02b2c3d001'));
        $this->assertInstanceOf(ConnectionInfo::class, $sut);
    }

    public function testItGetsValues(): void
    {
        $sut = new ConnectionInfo('1.0.0', 'v1.2.3', 'baz', new ClientUuid('f47ac10b-58cc-4372-a567-0e02b2c3d001'));
        $this->assertSame('1.0.0', $sut->getKeycloakVersion());
        $this->assertSame('v1.2.3', $sut->getExtensionVersion());
        $this->assertSame('baz', $sut->getClientId());
        $this->assertSame('f47ac10b-58cc-4372-a567-0e02b2c3d001', (string)$sut->getClientUuid());
    }

    public function testItCanBeJsonEncoded(): void
    {
        $sut = new ConnectionInfo('1.0.0', 'v1.2.3', 'baz', new ClientUuid('f47ac10b-58cc-4372-a567-0e02b2c3d001'));
        $this->assertSame(
            '{"keycloakVersion":"1.0.0","extensionVersion":"v1.2.3","clientId":"baz","clientUuid":"f47ac10b-58cc-4372-a567-0e02b2c3d001"}',
            json_encode($sut)
        );
    }

    public function testItCanBeHydratedFromCacheValue(): void
    {
        $sut = new ConnectionInfo('1.0.0', 'v1.2.3', 'baz', new ClientUuid('f47ac10b-58cc-4372-a567-0e02b2c3d001'));
        $sut2 = ConnectionInfo::fromArray($sut->jsonSerialize());
        $this->assertEquals($sut, $sut2);
    }

}
