<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Profiles;


use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Keycloak\Value\ConnectionConfig;
use Hawk\AuthClient\Profiles\ProfileLayer;
use Hawk\AuthClient\Profiles\ProfileStorage;
use Hawk\AuthClient\Profiles\ProfileUpdater;
use Hawk\AuthClient\Profiles\Structure\ProfileStructureBuilder;
use Hawk\AuthClient\Profiles\Structure\Util\ProfileStructureData;
use Hawk\AuthClient\Users\Value\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProfileLayer::class)]
class ProfileLayerTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new ProfileLayer(
            $this->createStub(ConnectionConfig::class),
            $this->createStub(KeycloakApiClient::class),
            $this->createStub(ProfileStorage::class)
        );
        $this->assertInstanceOf(ProfileLayer::class, $sut);
    }

    public function testItCanCreateAStructureDefinitionBuilder(): void
    {
        $config = $this->createStub(ConnectionConfig::class);
        $apiClient = $this->createStub(KeycloakApiClient::class);
        $apiClient->method('fetchProfileStructure')->willReturn($this->createStub(ProfileStructureData::class));
        $storage = $this->createStub(ProfileStorage::class);
        $sut = new ProfileLayer($config, $apiClient, $storage);

        $this->assertInstanceOf(ProfileStructureBuilder::class, $sut->define());
    }

    public function testItCanCreateAProfileUpdater(): void
    {
        $config = $this->createStub(ConnectionConfig::class);
        $apiClient = $this->createStub(KeycloakApiClient::class);
        $storage = $this->createStub(ProfileStorage::class);
        $sut = new ProfileLayer($config, $apiClient, $storage);

        $this->assertInstanceOf(ProfileUpdater::class, $sut->update($this->createStub(User::class)));
    }

}
