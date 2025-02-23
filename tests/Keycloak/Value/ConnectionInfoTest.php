<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Keycloak\Value;


use Hawk\AuthClient\Keycloak\Value\ConnectionInfo;
use Hawk\AuthClient\Tests\TestUtils\DummyUuid;
use Hawk\AuthClient\Util\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConnectionInfo::class)]
class ConnectionInfoTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new ConnectionInfo('foo', 'bar', 'baz', new DummyUuid(1), new DummyUuid(2));
        $this->assertInstanceOf(ConnectionInfo::class, $sut);
    }

    public function testItGetsValues(): void
    {
        $clientUuid = new DummyUuid(1);
        $serviceAccountId = new DummyUuid(2);
        $sut = new ConnectionInfo('1.0.0', 'v1.2.3', 'baz', $clientUuid, $serviceAccountId);
        $this->assertSame('1.0.0', $sut->getKeycloakVersion());
        $this->assertSame('v1.2.3', $sut->getExtensionVersion());
        $this->assertSame('baz', $sut->getClientId());
        $this->assertSame($clientUuid, $sut->getClientUuid());
        $this->assertSame($serviceAccountId, $sut->getClientServiceAccountUuid());
    }

    public function testItCanBeJsonEncoded(): void
    {
        $clientUuid = new DummyUuid(1);
        $serviceAccountId = new DummyUuid(2);
        $sut = new ConnectionInfo('1.0.0', 'v1.2.3', 'baz', $clientUuid, $serviceAccountId);
        $this->assertSame(
            '{"keycloakVersion":"1.0.0","extensionVersion":"v1.2.3","clientId":"baz","clientUuid":"' . $clientUuid . '","clientServiceAccountUuid":"' . $serviceAccountId . '"}',
            json_encode($sut)
        );
    }

    public function testItCanBeHydratedFromCacheValue(): void
    {
        $sut = new ConnectionInfo('1.0.0', 'v1.2.3', 'baz', new Uuid(new DummyUuid(1)), new Uuid(new DummyUuid(2)));
        $sut2 = ConnectionInfo::fromArray($sut->jsonSerialize());
        $this->assertEquals($sut, $sut2);
    }

}
