<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Profiles\Structure;


use Hawk\AuthClient\Keycloak\Value\ConnectionConfig;
use Hawk\AuthClient\Profiles\Structure\ProfileField;
use Hawk\AuthClient\Profiles\Structure\ProfileGroup;
use Hawk\AuthClient\Profiles\Structure\ProfileStructure;
use Hawk\AuthClient\Profiles\Structure\Util\ProfileStructureData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProfileStructure::class)]
class ProfileStructureTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new ProfileStructure(
            $this->createStub(ConnectionConfig::class),
            $this->createStub(ProfileStructureData::class)
        );
        $this->assertInstanceOf(ProfileStructure::class, $sut);
    }

    public function testItReturnsGroups(): void
    {
        $config = $this->createStub(ConnectionConfig::class);
        $config->method('getClientId')->willReturn('client1');
        $groupList = [['name' => 'group1'], ['name' => 'group2']];
        $data = $this->createMock(ProfileStructureData::class);
        $data->expects($this->once())->method('getGroups')->with($config, true)->willReturn($groupList);
        $data->expects($this->exactly(2))->method('getGroup')->willReturn([]);

        $sut = new ProfileStructure($config, $data);

        $groups = iterator_to_array($sut->getGroups(true));
        $this->assertContainsOnlyInstancesOf(ProfileGroup::class, $groups);
        $this->assertEquals(
            ['group1', 'group2'],
            array_map(
                fn(ProfileGroup $group) => $group->getName(),
                $groups
            )
        );
    }

    public function testItCanCheckIfAGroupExists(): void
    {
        $config = $this->createStub(ConnectionConfig::class);
        $config->method('getClientId')->willReturn('client1');
        $data = $this->createMock(ProfileStructureData::class);
        $data->expects($this->exactly(2))->method('getGroup')->willReturnCallback(fn($name) => $name === 'hawk.client1.group1' ? [] : null);

        $sut = new ProfileStructure($config, $data);

        $this->assertTrue($sut->hasGroup('group1'));
        $this->assertFalse($sut->hasGroup('group2'));
        // Second try should be cached -> otherwise the counter should throw an exception
        $this->assertTrue($sut->hasGroup('group1'));
        $this->assertFalse($sut->hasGroup('group2'));
    }

    public function testItCanGetOneGroup(): void
    {
        $config = $this->createStub(ConnectionConfig::class);
        $config->method('getClientId')->willReturn('client1');
        $data = $this->createMock(ProfileStructureData::class);
        $data->expects($this->exactly(2))->method('getGroup')->willReturnCallback(fn($name) => $name === 'hawk.client1.group1' ? [] : null);

        $sut = new ProfileStructure($config, $data);

        $group = $sut->getGroup('group1');
        $this->assertInstanceOf(ProfileGroup::class, $group);
        $this->assertEquals('group1', $group->getName());

        $group = $sut->getGroup('group1', 'client2');
        $this->assertNull($group);
    }

    public function testItReturnsFields(): void
    {
        $config = $this->createStub(ConnectionConfig::class);
        $config->method('getClientId')->willReturn('client1');
        $fieldList = [['name' => 'field1'], ['name' => 'field2']];
        $data = $this->createMock(ProfileStructureData::class);
        $data->expects($this->once())->method('getFields')->with($config, true)->willReturn($fieldList);
        $data->expects($this->exactly(2))->method('getField')->willReturn([]);

        $sut = new ProfileStructure($config, $data);

        $fields = iterator_to_array($sut->getFields(true));
        $this->assertContainsOnlyInstancesOf(ProfileField::class, $fields);
        $this->assertEquals(
            ['field1', 'field2'],
            array_map(
                fn(ProfileField $field) => $field->getName(),
                $fields
            )
        );
    }

    public function testItCanCheckIfAFieldExists(): void
    {
        $config = $this->createStub(ConnectionConfig::class);
        $config->method('getClientId')->willReturn('client1');
        $data = $this->createMock(ProfileStructureData::class);
        $data->expects($this->exactly(2))->method('getField')->willReturnCallback(fn($name) => $name === 'hawk.client1.field1' ? [] : null);

        $sut = new ProfileStructure($config, $data);

        $this->assertTrue($sut->hasField('field1'));
        $this->assertFalse($sut->hasField('field2'));
        // Second try should be cached -> otherwise the counter should throw an exception
        $this->assertTrue($sut->hasField('field1'));
        $this->assertFalse($sut->hasField('field2'));
    }

    public function testItCanGetOneField(): void
    {
        $config = $this->createStub(ConnectionConfig::class);
        $config->method('getClientId')->willReturn('client1');
        $data = $this->createMock(ProfileStructureData::class);
        $data->expects($this->exactly(2))->method('getField')->willReturnCallback(fn($name) => $name === 'hawk.client1.field1' ? [] : null);

        $sut = new ProfileStructure($config, $data);

        $field = $sut->getField('field1');
        $this->assertInstanceOf(ProfileField::class, $field);
        $this->assertEquals('field1', $field->getName());

        $field = $sut->getField('field1', 'client2');
        $this->assertNull($field);

        // Retry getting field1 again -> should hit cache
        $this->assertEquals('field1', $sut->getField('field1')?->getName());
    }

    public function testItCanJsonEncode(): void
    {
        $data = ['attributes' => [], 'groups' => []];
        $structureData = $this->createStub(ProfileStructureData::class);
        $structureData->method('jsonSerialize')->willReturn($data);
        $structure = new ProfileStructure($this->createStub(ConnectionConfig::class), $structureData);
        $this->assertEquals($data, $structure->jsonSerialize());
    }

}
