<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Profiles\Structure;


use Hawk\AuthClient\Exception\GroupDoesNotExistException;
use Hawk\AuthClient\Profiles\Structure\FieldInputTypes;
use Hawk\AuthClient\Profiles\Structure\ProfileFieldBuilder;
use Hawk\AuthClient\Profiles\Structure\ProfileGroupBuilder;
use Hawk\AuthClient\Profiles\Structure\ProfileStructureBuilder;
use Hawk\AuthClient\Profiles\Structure\Util\ProfileFieldData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProfileFieldBuilder::class)]
#[CoversClass(GroupDoesNotExistException::class)]
class ProfileFieldBuilderTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new ProfileFieldBuilder(
            'fullName',
            'name',
            $this->createStub(ProfileFieldData::class),
            $this->createStub(ProfileStructureBuilder::class)
        );
        $this->assertInstanceOf(ProfileFieldBuilder::class, $sut);
    }

    public function testItCanSetTheDisplayName(): void
    {
        $data = $this->createMock(ProfileFieldData::class);
        $data->expects($this->once())->method('setAttr')->with('displayName', 'my-name');
        $sut = new ProfileFieldBuilder('fullName', 'name', $data, $this->createStub(ProfileStructureBuilder::class));
        $sut->setDisplayName('my-name');
    }

    public function testItCanSetTheGroup(): void
    {
        $data = $this->createMock(ProfileFieldData::class);
        $data->expects($this->once())->method('setAttr')->with('group', 'my-group');
        $group = $this->createStub(ProfileGroupBuilder::class);
        $group->method('getFullName')->willReturn('my-group');
        $structure = $this->createStub(ProfileStructureBuilder::class);
        $structure->method('hasGroup')->willReturn(true);
        $structure->method('getGroup')->willReturn($group);
        $sut = new ProfileFieldBuilder('fullName', 'name', $data, $structure);
        $sut->setGroup('my-group');
    }

    public function testItFailsToSetTheGroupIfItDoesNotExist(): void
    {
        $this->expectException(GroupDoesNotExistException::class);
        $data = $this->createMock(ProfileFieldData::class);
        $data->expects($this->never())->method('setAttr');
        $structure = $this->createStub(ProfileStructureBuilder::class);
        $structure->method('hasGroup')->willReturn(false);
        $sut = new ProfileFieldBuilder('fullName', 'name', $data, $structure);
        $sut->setGroup('my-group');
    }

    public function testItWillRemoveTheGroupIfNullIsGiven(): void
    {
        $data = $this->createMock(ProfileFieldData::class);
        $data->expects($this->once())->method('setAttr')->with('group', null);
        $structure = $this->createStub(ProfileStructureBuilder::class);
        $structure->method('hasGroup')->willReturn(true);
        $sut = new ProfileFieldBuilder('fullName', 'name', $data, $structure);
        $sut->setGroup(null);
    }

    public function testItCanSetMultiValued(): void
    {
        $data = $this->createMock(ProfileFieldData::class);
        $data->expects($this->once())->method('setAttr')->with('multivalued', true);
        $sut = new ProfileFieldBuilder('fullName', 'name', $data, $this->createStub(ProfileStructureBuilder::class));
        $sut->setMultiValued();
    }

    public function testItCanSetRequiredForUser(): void
    {
        $data = $this->createMock(ProfileFieldData::class);
        $data->expects($this->once())->method('setAttr')->with('required', ['roles' => ['user']]);
        $sut = new ProfileFieldBuilder('fullName', 'name', $data, $this->createStub(ProfileStructureBuilder::class));
        $sut->setRequiredForUser();
    }

    public function testItCanSetRequiredForAdmin(): void
    {
        $data = $this->createMock(ProfileFieldData::class);
        $data->expects($this->once())->method('setAttr')->with('required', ['roles' => ['admin']]);
        $sut = new ProfileFieldBuilder('fullName', 'name', $data, $this->createStub(ProfileStructureBuilder::class));
        $sut->setRequiredForAdmin();
    }

    public function testItCanSetUserCanView(): void
    {
        $data = $this->createMock(ProfileFieldData::class);
        $data->expects($this->once())->method('setAttr')->with('permissions', ['view' => ['user']]);
        $sut = new ProfileFieldBuilder('fullName', 'name', $data, $this->createStub(ProfileStructureBuilder::class));
        $sut->setUserCanView();
    }

    public function testItCanSetUserCanEdit(): void
    {
        $data = $this->createMock(ProfileFieldData::class);
        $data->expects($this->once())->method('setAttr')->with('permissions', ['edit' => ['user']]);
        $sut = new ProfileFieldBuilder('fullName', 'name', $data, $this->createStub(ProfileStructureBuilder::class));
        $sut->setUserCanEdit();
    }

    public function testItCanSetAdminCanView(): void
    {
        $data = $this->createMock(ProfileFieldData::class);
        $data->expects($this->once())->method('setAttr')->with('permissions', ['view' => ['admin']]);
        $sut = new ProfileFieldBuilder('fullName', 'name', $data, $this->createStub(ProfileStructureBuilder::class));
        $sut->setAdminCanView();
    }

    public function testItCanSetAdminCanEdit(): void
    {
        $data = $this->createMock(ProfileFieldData::class);
        $data->expects($this->once())->method('setAttr')->with('permissions', ['edit' => ['admin']]);
        $sut = new ProfileFieldBuilder('fullName', 'name', $data, $this->createStub(ProfileStructureBuilder::class));
        $sut->setAdminCanEdit();
    }

    public function testItCanSetTheInputTypeWithString(): void
    {
        $data = $this->createMock(ProfileFieldData::class);
        $data->expects($this->once())->method('setAttr')->with('annotations', ['inputType' => 'text']);
        $sut = new ProfileFieldBuilder('fullName', 'name', $data, $this->createStub(ProfileStructureBuilder::class));
        $sut->setInputType('text');
    }

    public function testItCanSetTheInputTypeWithEnum(): void
    {
        $data = $this->createMock(ProfileFieldData::class);
        $data->expects($this->once())->method('setAttr')->with('annotations', ['inputType' => 'html5-date']);
        $sut = new ProfileFieldBuilder('fullName', 'name', $data, $this->createStub(ProfileStructureBuilder::class));
        $sut->setInputType(FieldInputTypes::DATE);
    }

    public function testItCanSetHelperTextBefore(): void
    {
        $data = $this->createMock(ProfileFieldData::class);
        $data->expects($this->once())->method('setAttr')->with('annotations', ['inputHelperTextBefore' => 'my-text']);
        $sut = new ProfileFieldBuilder('fullName', 'name', $data, $this->createStub(ProfileStructureBuilder::class));
        $sut->setHelperTextBefore('my-text');
    }

    public function testItCanSetHelperTextAfter(): void
    {
        $data = $this->createMock(ProfileFieldData::class);
        $data->expects($this->once())->method('setAttr')->with('annotations', ['inputHelperTextAfter' => 'my-text']);
        $sut = new ProfileFieldBuilder('fullName', 'name', $data, $this->createStub(ProfileStructureBuilder::class));
        $sut->setHelperTextAfter('my-text');
    }

    public function testItCanSetThePlaceholder(): void
    {
        $data = $this->createMock(ProfileFieldData::class);
        $data->expects($this->once())->method('setAttr')->with('annotations', ['inputTypePlaceholder' => 'my-placeholder']);
        $sut = new ProfileFieldBuilder('fullName', 'name', $data, $this->createStub(ProfileStructureBuilder::class));
        $sut->setPlaceholder('my-placeholder');
    }

    public static function provideTestItCanSetTheDoubleValidatorData(): iterable
    {
        yield 'default' => [null, null, ['min' => -9999999, 'max' => 9999999]];
        yield 'min' => [1, null, ['min' => 1, 'max' => 9999999]];
        yield 'max' => [null, 1, ['min' => -9999999, 'max' => 1]];
        yield 'both' => [1, 2, ['min' => 1, 'max' => 2]];
        yield 'double' => [1.1, 2.2, ['min' => 1.1, 'max' => 2.2]];
    }

    #[DataProvider('provideTestItCanSetTheDoubleValidatorData')]
    public function testItCanSetTheDoubleValidator(mixed $min, mixed $max, array $expected): void
    {
        $data = $this->createMock(ProfileFieldData::class);
        $data->expects($this->once())->method('setAttr')->with('validations', ['double' => $expected]);
        $sut = new ProfileFieldBuilder('fullName', 'name', $data, $this->createStub(ProfileStructureBuilder::class));
        $sut->setDoubleValidator($min, $max);
    }

    public static function provideTestItCanSetTheIntegerValidatorData(): iterable
    {
        yield 'default' => [null, null, ['min' => -9999999, 'max' => 9999999]];
        yield 'min' => [1, null, ['min' => 1, 'max' => 9999999]];
        yield 'max' => [null, 1, ['min' => -9999999, 'max' => 1]];
        yield 'both' => [1, 2, ['min' => 1, 'max' => 2]];
    }

    #[DataProvider('provideTestItCanSetTheIntegerValidatorData')]
    public function testItCanSetTheIntegerValidator(mixed $min, mixed $max, array $expected): void
    {
        $data = $this->createMock(ProfileFieldData::class);
        $data->expects($this->once())->method('setAttr')->with('validations', ['integer' => $expected]);
        $sut = new ProfileFieldBuilder('fullName', 'name', $data, $this->createStub(ProfileStructureBuilder::class));
        $sut->setIntegerValidator($min, $max);
    }

    public static function provideTestItCanSetEmailValidatorData(): iterable
    {
        yield 'default' => [null, ['max-local-length' => 64]];
        yield 'less than 1' => [0, ['max-local-length' => 1]];
        yield 'higher than default' => [100, ['max-local-length' => 100]];
    }

    #[DataProvider('provideTestItCanSetEmailValidatorData')]
    public function testItCanSetEmailValidator(int|null $maxLocalLength, array $expected): void
    {
        $data = $this->createMock(ProfileFieldData::class);
        $data->expects($this->once())->method('setAttr')->with('validations', ['email' => $expected]);
        $sut = new ProfileFieldBuilder('fullName', 'name', $data, $this->createStub(ProfileStructureBuilder::class));
        $sut->setEmailValidator($maxLocalLength);
    }

    public function testItCanSetDataValidator(): void
    {
        $data = $this->createMock(ProfileFieldData::class);
        $data->expects($this->once())->method('setAttr')->with('validations', ['iso-date' => []]);
        $sut = new ProfileFieldBuilder('fullName', 'name', $data, $this->createStub(ProfileStructureBuilder::class));
        $sut->setDateValidator();
    }

    public static function provideTestItCanSetMultiValueValidatorData(): iterable
    {
        yield 'default' => [null, null, ['min' => 0, 'max' => 1]];
        yield 'min' => [1, null, ['min' => 1, 'max' => 1]];
        yield 'max' => [null, 9999999, ['min' => 0, 'max' => 9999999]];
        yield 'both' => [1, 2, ['min' => 1, 'max' => 2]];
        yield 'both less than 0' => [-1, -2, ['min' => 0, 'max' => 0]];
    }

    #[DataProvider('provideTestItCanSetMultiValueValidatorData')]
    public function testItCanSetMultiValueValidator(int|null $min, int|null $max, array $expected): void
    {
        $data = $this->createMock(ProfileFieldData::class);
        $data->expects($this->once())->method('setAttr')->with('validations', ['multi-value' => $expected]);
        $sut = new ProfileFieldBuilder('fullName', 'name', $data, $this->createStub(ProfileStructureBuilder::class));
        $sut->setMultiValueValidator($min, $max);
    }

    public function testItCanSetOptionsValidator(): void
    {
        $options = ['foo', 'bar', 'baz'];
        $data = $this->createMock(ProfileFieldData::class);
        $data->expects($this->once())->method('setAttr')->with('validations', ['options' => ['options' => $options]]);
        $sut = new ProfileFieldBuilder('fullName', 'name', $data, $this->createStub(ProfileStructureBuilder::class));
        $sut->setOptionsValidator($options);
    }

    public function testItCanSetPatternValidator(): void
    {
        $pattern = '/^foo/';
        $data = $this->createMock(ProfileFieldData::class);
        $data->expects($this->once())->method('setAttr')->with('validations', ['pattern' => ['pattern' => $pattern, 'error-message' => '']]);
        $sut = new ProfileFieldBuilder('fullName', 'name', $data, $this->createStub(ProfileStructureBuilder::class));
        $sut->setPatternValidator($pattern);
    }

    public function testItCanSetARawAttribute(): void
    {
        $data = $this->createMock(ProfileFieldData::class);
        $data->expects($this->once())->method('setAttr')->with('customAttribute', 'my-value');
        $sut = new ProfileFieldBuilder('fullName', 'name', $data, $this->createStub(ProfileStructureBuilder::class));
        $sut->setRawAttribute('customAttribute', 'my-value');
    }

    public function testItCanSetAnAnnotation(): void
    {
        $data = $this->createMock(ProfileFieldData::class);
        $data->expects($this->once())->method('setAttr')->with('annotations', ['annotation' => ['custom' => 'data']]);
        $sut = new ProfileFieldBuilder('fullName', 'name', $data, $this->createStub(ProfileStructureBuilder::class));
        $sut->setAnnotation('annotation', ['custom' => 'data']);
    }


}
