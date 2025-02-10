<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Profiles\Structure\Util;


use Hawk\AuthClient\Keycloak\Value\ConnectionConfig;
use Hawk\AuthClient\Profiles\Structure\Util\ProfileStructureData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProfileStructureData::class)]
class ProfileStructureDataTest extends TestCase
{
    public function testItCanConstruct(): void
    {
        $sut = new ProfileStructureData([]);
        $this->assertInstanceOf(ProfileStructureData::class, $sut);
    }

    public function testItCanGetASingleField(): void
    {
        // Attributes not given
        $sut = new ProfileStructureData([]);
        $this->assertNull($sut->getField('test-field'));

        $sut = new ProfileStructureData([
            'attributes' => [
                'other-field' => ['name' => 'other-field']
            ]
        ]);

        // Attributes given, but field not included
        $this->assertNull($sut->getField('test-field'));

        // Attributes given, field included
        $this->assertEquals(['name' => 'other-field'], $sut->getField('other-field'));

        // No change -> not dirty
        $this->assertFalse($sut->isDirty());
    }

    public function testItCanSetAField(): void
    {
        // Attributes not given
        $sut = new ProfileStructureData([]);
        $sut->setField('test-field', ['name' => 'test-field']);
        $this->assertEquals(['name' => 'test-field'], $sut->getField('test-field'));
        $this->assertTrue($sut->isDirty());

        // Set a new field, but keep old ones
        $sut = new ProfileStructureData([
            'attributes' => [
                'other-field' => ['name' => 'other-field']
            ]
        ]);
        $sut->setField('test-field', ['name' => 'test-field']);
        $this->assertEquals(['name' => 'test-field'], $sut->getField('test-field'));
        $this->assertEquals(['name' => 'other-field'], $sut->getField('other-field'));
        $this->assertTrue($sut->isDirty());

        $sut->markClean();
        $this->assertFalse($sut->isDirty());

        // Update an existing field
        $sut->setField('test-field', ['name' => 'test-field', 'foo' => 'bar']);
        $this->assertEquals(['name' => 'test-field', 'foo' => 'bar'], $sut->getField('test-field'));
        $sut->setField('test-field', ['name' => 'updated-field']);
        $this->assertEquals(['name' => 'updated-field'], $sut->getField('updated-field'));
        $this->assertNull($sut->getField('test-field'));
        $this->assertTrue($sut->isDirty());
    }

    public function testItCanRemoveAField(): void
    {
        // Attributes not given
        $sut = new ProfileStructureData([]);
        $sut->removeField('test-field');
        $this->assertNull($sut->getField('test-field'));
        $this->assertFalse($sut->isDirty());

        // Remove a field
        $sut = new ProfileStructureData([
            'attributes' => [
                'other-field' => ['name' => 'other-field']
            ]
        ]);
        $sut->removeField('other-field');
        $this->assertNull($sut->getField('other-field'));
        $this->assertTrue($sut->isDirty());
    }

    public function testItCanGetAllFields(): void
    {
        $config = $this->createStub(ConnectionConfig::class);
        $config->method('getClientId')->willReturn('client-1');

        // Attributes not given
        $sut = new ProfileStructureData([]);
        $this->assertEquals([], [...$sut->getFields($config)]);

        $globalField = ['name' => 'global-field'];
        $client1Field = ['name' => 'hawk.client-1.field', 'group' => 'group-1'];
        $client2Field = ['name' => 'hawk.client-2.field', 'group' => 'group-2'];
        $client1Field2 = ['name' => 'hawk.client-1.field-2', 'group' => 'group-2'];
        $allFields = [$globalField, $client1Field, $client2Field, $client1Field2];

        $sut = new ProfileStructureData(['attributes' => $allFields]);

        // Local only
        $this->assertEquals([$client1Field, $client1Field2], [...$sut->getFields($config)]);
        $this->assertEquals([$client1Field], [...$sut->getFields($config, group: 'group-1')]);
        $this->assertEquals([$client1Field2], [...$sut->getFields($config, group: 'group-2')]);

        // Global only
        $this->assertEquals([$globalField], [...$sut->getFields($config, false)]);

        // Other client
        $this->assertEquals([$client2Field], [...$sut->getFields($config, 'client-2')]);
        $this->assertEquals([], [...$sut->getFields($config, 'client-2', 'group-1')]);
        $this->assertEquals([$client2Field], [...$sut->getFields($config, 'client-2', 'group-2')]);

        // ALL fields
        $this->assertEquals($allFields, [...$sut->getFields($config, true)]);
    }

    public function testItCanGetASingleGroup(): void
    {
        // Groups not given
        $sut = new ProfileStructureData([]);
        $this->assertNull($sut->getGroup('test-group'));

        $sut = new ProfileStructureData([
            'groups' => [
                'other-group' => ['name' => 'other-group']
            ]
        ]);

        // Groups given, but group not included
        $this->assertNull($sut->getGroup('test-group'));

        // Groups given, group included
        $this->assertEquals(['name' => 'other-group'], $sut->getGroup('other-group'));

        // No change -> not dirty
        $this->assertFalse($sut->isDirty());
    }

    public function testItCanSetAGroup(): void
    {
        // Groups not given
        $sut = new ProfileStructureData([]);
        $sut->setGroup('test-group', ['name' => 'test-group']);
        $this->assertEquals(['name' => 'test-group'], $sut->getGroup('test-group'));
        $this->assertTrue($sut->isDirty());

        // Set a new group, but keep old ones
        $sut = new ProfileStructureData([
            'groups' => [
                'other-group' => ['name' => 'other-group']
            ]
        ]);
        $sut->setGroup('test-group', ['name' => 'test-group']);
        $this->assertEquals(['name' => 'test-group'], $sut->getGroup('test-group'));
        $this->assertEquals(['name' => 'other-group'], $sut->getGroup('other-group'));
        $this->assertTrue($sut->isDirty());

        $sut->markClean();
        $this->assertFalse($sut->isDirty());

        // Update an existing group
        $sut->setGroup('test-group', ['name' => 'test-group', 'foo' => 'bar']);
        $this->assertEquals(['name' => 'test-group', 'foo' => 'bar'], $sut->getGroup('test-group'));
        $sut->setGroup('test-group', ['name' => 'updated-group']);
        $this->assertEquals(['name' => 'updated-group'], $sut->getGroup('updated-group'));
        $this->assertNull($sut->getGroup('test-group'));
        $this->assertTrue($sut->isDirty());
    }

    public function testItCanRemoveAGroup(): void
    {
        // Groups not given
        $sut = new ProfileStructureData([]);
        $sut->removeGroup('test-group');
        $this->assertNull($sut->getGroup('test-group'));
        $this->assertFalse($sut->isDirty());

        // Remove a group
        $sut = new ProfileStructureData([
            'groups' => [
                'other-group' => ['name' => 'other-group']
            ]
        ]);
        $sut->removeGroup('other-group');
        $this->assertNull($sut->getGroup('other-group'));
        $this->assertTrue($sut->isDirty());
    }

    public function testItCanGetAllGroups(): void
    {
        $config = $this->createStub(ConnectionConfig::class);
        $config->method('getClientId')->willReturn('client-1');

        // Groups not given
        $sut = new ProfileStructureData([]);
        $this->assertEquals([], [...$sut->getGroups($config)]);

        $globalGroup = ['name' => 'global-group'];
        $client1Group = ['name' => 'hawk.client-1.group'];
        $client2Group = ['name' => 'hawk.client-2.group'];
        $client1Group2 = ['name' => 'hawk.client-1.group-2'];
        $allGroups = [$globalGroup, $client1Group, $client2Group, $client1Group2];

        $sut = new ProfileStructureData(['groups' => $allGroups]);

        // Local only
        $this->assertEquals([$client1Group, $client1Group2], [...$sut->getGroups($config)]);

        // Global only
        $this->assertEquals([$globalGroup], [...$sut->getGroups($config, false)]);

        // Other client
        $this->assertEquals([$client2Group], [...$sut->getGroups($config, 'client-2')]);

        // Nothing, if client does not match
        $this->assertEquals([], [...$sut->getGroups($config, 'client-3')]);

        // ALL groups
        $this->assertEquals($allGroups, [...$sut->getGroups($config, true)]);
    }

    public function testItCanDetectAndResetTheDirtyState(): void
    {
        $sut = new ProfileStructureData([]);
        $this->assertFalse($sut->isDirty());

        $sut->setField('test-field', ['name' => 'test-field']);
        $this->assertTrue($sut->isDirty());

        $sut->markClean();
        $this->assertFalse($sut->isDirty());

        $sut->setGroup('test-group', ['name' => 'test-group']);
        $this->assertTrue($sut->isDirty());

        $sut->markClean();
        $this->assertFalse($sut->isDirty());
    }

    public function testItCanBeJsonEncoded(): void
    {
        $expected = <<<JSON
{
  "attributes": [
    {
      "name": "test-attribute",
      "annotations": {
        "test-annotation": "test-value"
      },
      "group": "test-group",
      "required": true,
      "validations": {}
    },
    {
      "name": "test-attribute",
      "annotations": {
        "test-annotation": "test-value"
      },
      "group": "test-group",
      "permissions": {
        "user": {
          "view": true,
          "edit": true
        },
        "admin": {
          "view": true,
          "edit": true
        }
      },
      "required": {
        "roles": {
          "user": true,
          "admin": true
        }
      },
      "validations": {
        "double": {},
        "email": {
          "message": "test-message",
          "params": {
            "test-param": "test-value"
          }
        }
      }
    }
  ],
  "groups": [
    {
      "name": "test-group",
      "annotations": {
        "test-annotation": "test-value"
      }
    }
  ]
}
JSON;

        $data = [
            'attributes' => [
                [
                    'name' => 'test-attribute',
                    'annotations' => ['test-annotation' => 'test-value'],
                    'group' => 'test-group',
                    'required' => true,
                    'validations' => []
                ],
                [
                    'name' => 'test-attribute',
                    'annotations' => ['test-annotation' => 'test-value'],
                    'group' => 'test-group',
                    'required' => ['roles' => ['user' => true, 'admin' => true]],
                    'permissions' => [
                        'user' => ['view' => true, 'edit' => true],
                        'admin' => ['view' => true, 'edit' => true]
                    ],
                    'validations' => [
                        'double' => [],
                        'email' => [
                            'message' => 'test-message',
                            'params' => ['test-param' => 'test-value']
                        ]
                    ]
                ]
            ],
            'groups' => [
                [
                    'name' => 'test-group',
                    'annotations' => ['test-annotation' => 'test-value']
                ]
            ]
        ];

        $sut = new ProfileStructureData($data);

        $this->assertJsonStringEqualsJsonString($expected, json_encode($sut));
    }

}
