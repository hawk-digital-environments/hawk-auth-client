<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Profiles\Structure;


use Hawk\AuthClient\Profiles\Structure\FieldInputTypes;
use Hawk\AuthClient\Profiles\Structure\ProfileField;
use Hawk\AuthClient\Profiles\Structure\Util\ProfileFieldData;
use Hawk\AuthClient\Profiles\Structure\Util\ProfileStructureData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProfileField::class)]
class ProfileFieldTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new ProfileField(
            'fullName',
            'name',
            $this->createStub(ProfileFieldData::class)
        );
        $this->assertInstanceOf(ProfileField::class, $sut);
    }

    public function testItCanGetAllSetValues(): void
    {
        $annotations = [
            'inputType' => FieldInputTypes::TEXTAREA->value,
            'inputHelperTextBefore' => 'before',
            'inputHelperTextAfter' => 'after',
            'inputTypePlaceholder' => 'placeholder',
            'annotation' => [
                'custom' => 'data'
            ]
        ];
        $validations = [
            'uri' => [],
            'length' => [
                'min' => 1,
                'max' => 255
            ]
        ];
        $data = $this->createStub(ProfileStructureData::class);
        $data->method('getField')
            ->willReturn([
                'displayName' => 'displayName',
                'displayDescription' => 'description',
                'customAttribute' => 'customValue',
                'group' => 'my-group',
                'multivalued' => true,
                'required' => true,
                'readOnly' => false,
                'annotations' => $annotations,
                'validations' => $validations
            ]);
        $fieldData = new ProfileFieldData($data, 'baseKey');
        $sut = new ProfileField('fullName', 'name', $fieldData);

        $this->assertEquals('name', $sut->getName());
        $this->assertEquals('fullName', $sut->getFullName());
        $this->assertEquals('displayName', $sut->getDisplayName());
        $this->assertEquals('my-group', $sut->getGroup());
        $this->assertTrue($sut->isMultivalued());
        $this->assertTrue($sut->isRequired());
        $this->assertFalse($sut->isReadOnly());
        $this->assertEquals(FieldInputTypes::TEXTAREA->value, $sut->getInputType());
        $this->assertEquals('before', $sut->getHelperTextBefore());
        $this->assertEquals('after', $sut->getHelperTextAfter());
        $this->assertEquals('placeholder', $sut->getPlaceholder());
        $this->assertEquals($validations['uri'], $sut->getValidation('uri'));
        $this->assertEquals($validations['length'], $sut->getValidation('length'));
        $this->assertEquals($validations, $sut->getValidations());
        $this->assertEquals('customValue', $sut->getRawAttribute('customAttribute'));
        $this->assertEquals(['custom' => 'data'], $sut->getAnnotation('annotation'));
        $this->assertEquals($annotations, $sut->getAnnotations());
    }

    public function testItCanBeStringified(): void
    {
        $sut = new ProfileField('fullName', 'name', $this->createStub(ProfileFieldData::class));

        $this->assertEquals('fullName', (string)$sut);
    }

    public function testItCanBeJsonEncoded(): void
    {
        $data = $this->createStub(ProfileFieldData::class);
        $data->method('jsonSerialize')->willReturn(['key' => 'value']);
        $sut = new ProfileField('fullName', 'name', $data);

        $this->assertEquals('{"key":"value"}', json_encode($sut));
    }

}
