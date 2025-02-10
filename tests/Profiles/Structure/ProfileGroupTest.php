<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Profiles\Structure;


use Hawk\AuthClient\Profiles\Structure\ProfileGroup;
use Hawk\AuthClient\Profiles\Structure\Util\ProfileGroupData;
use Hawk\AuthClient\Profiles\Structure\Util\ProfileStructureData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProfileGroup::class)]
class ProfileGroupTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new ProfileGroup(
            'fullName',
            'name',
            $this->createStub(ProfileGroupData::class),
        );
        $this->assertInstanceOf(ProfileGroup::class, $sut);
    }

    public function testItCanGetAllSetValues(): void
    {
        $data = $this->createStub(ProfileStructureData::class);
        $data->method('getGroup')
            ->willReturn([
                'displayHeader' => 'displayName',
                'displayDescription' => 'description',
                'customAttribute' => 'customValue',
                'annotations' => [
                    'annotation' => [
                        'custom' => 'data'
                    ]
                ]
            ]);
        $groupData = new ProfileGroupData($data, 'baseKey');
        $sut = new ProfileGroup('fullName', 'name', $groupData);

        $this->assertEquals('name', $sut->getName());
        $this->assertEquals('fullName', $sut->getFullName());
        $this->assertEquals('displayName', $sut->getDisplayName());
        $this->assertEquals('description', $sut->getDisplayDescription());
        $this->assertEquals('customValue', $sut->getRawAttribute('customAttribute'));
        $this->assertEquals(['custom' => 'data'], $sut->getAnnotation('annotation'));
        $this->assertEquals(['annotation' => ['custom' => 'data']], $sut->getAnnotations());
    }

    public function testItCanGetAllValuesWithFallbacks(): void
    {
        $data = $this->createStub(ProfileStructureData::class);
        $data->method('getGroup')
            ->willReturn([]);
        $groupData = new ProfileGroupData($data, 'baseKey');
        $sut = new ProfileGroup('fullName', 'name', $groupData);

        $this->assertEquals('name', $sut->getName());
        $this->assertEquals('fullName', $sut->getFullName());
        $this->assertEquals('name', $sut->getDisplayName());
        $this->assertEquals('', $sut->getDisplayDescription());
        $this->assertEquals(null, $sut->getRawAttribute('customAttribute'));
        $this->assertEquals(null, $sut->getAnnotation('annotation'));
    }

    public function testItCanBeStringified(): void
    {
        $sut = new ProfileGroup('fullName', 'name', $this->createStub(ProfileGroupData::class));

        $this->assertEquals('fullName', (string)$sut);
    }

    public function testItCanBeJsonEncoded(): void
    {
        $data = $this->createStub(ProfileGroupData::class);
        $data->method('jsonSerialize')->willReturn(['key' => 'value']);
        $sut = new ProfileGroup('fullName', 'name', $data);
        $this->assertJsonStringEqualsJsonString('{"key":"value"}', json_encode($sut));
    }

}
