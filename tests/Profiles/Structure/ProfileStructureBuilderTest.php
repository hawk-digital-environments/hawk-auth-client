<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Profiles\Structure;


use Hawk\AuthClient\Exception\CanNotRemoveReferencedGroupException;
use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Keycloak\Value\ConnectionConfig;
use Hawk\AuthClient\Profiles\Structure\ProfileFieldBuilder;
use Hawk\AuthClient\Profiles\Structure\ProfileGroupBuilder;
use Hawk\AuthClient\Profiles\Structure\ProfileStructureBuilder;
use Hawk\AuthClient\Profiles\Structure\Util\ProfileStructureData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProfileStructureBuilder::class)]
#[CoversClass(CanNotRemoveReferencedGroupException::class)]
class ProfileStructureBuilderTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new ProfileStructureBuilder(
            $this->createStub(ConnectionConfig::class),
            $this->createStub(ProfileStructureData::class),
            $this->createStub(KeycloakApiClient::class)
        );
        $this->assertInstanceOf(ProfileStructureBuilder::class, $sut);
    }

    public function testItCanGetAnExistingGroup(): void
    {
        $connection = $this->createStub(ConnectionConfig::class);
        $connection->method('getClientId')->willReturn('clientId');
        $data = $this->createMock(ProfileStructureData::class);
        $data->expects($this->exactly(2))->method('getGroup')->with('hawk.clientId.testGroup')->willReturn([
            'name' => 'hawk.clientId.testGroup',
            'displayHeader' => 'test header',
        ]);
        $data->expects($this->never())->method('setGroup');
        $sut = new ProfileStructureBuilder($connection, $data, $this->createStub(KeycloakApiClient::class));
        $group = $sut->getGroup('testGroup');
        $this->assertInstanceOf(ProfileGroupBuilder::class, $group);
        $this->assertEquals('hawk.clientId.testGroup', $group->getFullName());
        $this->assertEquals('testGroup', $group->getName());
        $this->assertEquals('test header', $group->getDisplayName());
    }

    public function testItCreatesANewGroupIfUnknownIsRequested(): void
    {
        $connection = $this->createStub(ConnectionConfig::class);
        $connection->method('getClientId')->willReturn('clientId');
        $data = $this->createMock(ProfileStructureData::class);
        $invoker = $this->exactly(2);
        $setData = [
            'name' => 'hawk.clientId.testGroup',
            'displayHeader' => '${hawk.clientId.testGroup}',
            'displayDescription' => '',
            'annotations' => []
        ];
        $data->expects($invoker)->method('getGroup')->with('hawk.clientId.testGroup')->willReturnCallback(function () use ($invoker, $setData) {
            if ($invoker->numberOfInvocations() === 1) {
                return null;
            }
            return $setData;
        });
        $data->expects($this->once())->method('setGroup')->with('hawk.clientId.testGroup', $setData);
        $sut = new ProfileStructureBuilder($connection, $data, $this->createStub(KeycloakApiClient::class));
        $group = $sut->getGroup('testGroup');
        $this->assertInstanceOf(ProfileGroupBuilder::class, $group);
        $this->assertEquals('hawk.clientId.testGroup', $group->getFullName());
        $this->assertEquals('testGroup', $group->getName());
        $this->assertEquals('${hawk.clientId.testGroup}', $group->getDisplayName());
    }

    public function testItCanRemoveAnUnusedGroup(): void
    {
        $connection = $this->createStub(ConnectionConfig::class);
        $connection->method('getClientId')->willReturn('clientId');
        $data = new ProfileStructureData([]);
        $sut = new ProfileStructureBuilder($connection, $data, $this->createStub(KeycloakApiClient::class));
        $group = $sut->getGroup('testGroup');
        $this->assertTrue($sut->hasGroup('testGroup'));
        $this->assertSame($group, $sut->getGroup('testGroup'));

        $sut->removeGroup('testGroup');

        $this->assertFalse($sut->hasGroup('testGroup'));
        $this->assertNotSame($group, $sut->getGroup('testGroup'));
    }

    public function testItFailsToRemoveAGroupStillUsedByAField(): void
    {
        $connection = $this->createStub(ConnectionConfig::class);
        $connection->method('getClientId')->willReturn('clientId');
        $data = new ProfileStructureData([]);
        $sut = new ProfileStructureBuilder($connection, $data, $this->createStub(KeycloakApiClient::class));

        $group = $sut->getGroup('testGroup');
        $field = $sut->getField('testField');
        $field->setGroup($group);

        $this->expectException(CanNotRemoveReferencedGroupException::class);

        $sut->removeGroup($group);
    }

    public function testItCanGetAnExistingField(): void
    {
        $connection = $this->createStub(ConnectionConfig::class);
        $connection->method('getClientId')->willReturn('clientId');
        $data = $this->createMock(ProfileStructureData::class);
        $data->expects($this->exactly(2))->method('getField')->with('hawk.clientId.testField')->willReturn([
            'name' => 'hawk.clientId.testField',
            'displayName' => 'test field',
        ]);
        $data->expects($this->never())->method('setField');
        $sut = new ProfileStructureBuilder($connection, $data, $this->createStub(KeycloakApiClient::class));
        $field = $sut->getField('testField');
        $this->assertInstanceOf(ProfileFieldBuilder::class, $field);
        $this->assertEquals('hawk.clientId.testField', $field->getFullName());
        $this->assertEquals('testField', $field->getName());
        $this->assertEquals('test field', $field->getDisplayName());
    }

    public function testItCreatesANewFieldIfUnknownIsRequested(): void
    {
        $connection = $this->createStub(ConnectionConfig::class);
        $connection->method('getClientId')->willReturn('clientId');
        $data = $this->createMock(ProfileStructureData::class);
        $invoker = $this->exactly(2);
        $setData = [
            'name' => 'hawk.clientId.testField',
            'displayName' => '${hawk.clientId.testField}',
            'validations' => [],
            'permissions' => [
                'view' => ['admin', 'user'],
                'edit' => ['admin', 'user']
            ],
            'annotations' => [],
            'multivalued' => false
        ];
        $data->expects($invoker)->method('getField')->with('hawk.clientId.testField')->willReturnCallback(function () use ($invoker, $setData) {
            if ($invoker->numberOfInvocations() === 1) {
                return null;
            }
            return $setData;
        });
        $data->expects($this->once())->method('setField')->with('hawk.clientId.testField', $setData);
        $sut = new ProfileStructureBuilder($connection, $data, $this->createStub(KeycloakApiClient::class));
        $field = $sut->getField('testField');
        $this->assertInstanceOf(ProfileFieldBuilder::class, $field);
        $this->assertEquals('hawk.clientId.testField', $field->getFullName());
        $this->assertEquals('testField', $field->getName());
        $this->assertEquals('${hawk.clientId.testField}', $field->getDisplayName());
    }

    public function testItCanRemoveAField(): void
    {
        $connection = $this->createStub(ConnectionConfig::class);
        $connection->method('getClientId')->willReturn('clientId');
        $data = new ProfileStructureData([]);
        $sut = new ProfileStructureBuilder($connection, $data, $this->createStub(KeycloakApiClient::class));
        $field = $sut->getField('testField');
        $this->assertTrue($sut->hasField('testField'));
        $this->assertSame($field, $sut->getField('testField'));

        $sut->removeField('testField');

        $this->assertFalse($sut->hasField('testField'));
        $this->assertNotSame($field, $sut->getField('testField'));
    }

    public function testItCanSaveDirtyData(): void
    {
        $data = $this->createMock(ProfileStructureData::class);
        $data->expects($this->once())->method('isDirty')->willReturn(true);
        $data->expects($this->once())->method('markClean');
        $api = $this->createMock(KeycloakApiClient::class);
        $api->expects($this->once())->method('updateProfileStructure')->with($data);
        $sut = new ProfileStructureBuilder($this->createStub(ConnectionConfig::class), $data, $api);
        $sut->save();
    }

    public function testItDoesNotDoAnythingOnSaveWithCleanData(): void
    {
        $data = $this->createMock(ProfileStructureData::class);
        $data->expects($this->once())->method('isDirty')->willReturn(false);
        $data->expects($this->never())->method('markClean');
        $api = $this->createMock(KeycloakApiClient::class);
        $api->expects($this->never())->method('updateProfileStructure');
        $sut = new ProfileStructureBuilder($this->createStub(ConnectionConfig::class), $data, $api);
        $sut->save();
    }

}
