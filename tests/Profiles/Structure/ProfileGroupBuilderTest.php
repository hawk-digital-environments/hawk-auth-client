<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Profiles\Structure;


use Hawk\AuthClient\Profiles\Structure\ProfileGroupBuilder;
use Hawk\AuthClient\Profiles\Structure\Util\ProfileGroupData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProfileGroupBuilder::class)]
class ProfileGroupBuilderTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new ProfileGroupBuilder(
            'fullName',
            'name',
            $this->createStub(ProfileGroupData::class),
        );
        $this->assertInstanceOf(ProfileGroupBuilder::class, $sut);
    }

    public function testItCanSetTheDisplayName(): void
    {
        $data = $this->createMock(ProfileGroupData::class);
        $data->expects($this->once())->method('setAttr')->with('displayHeader', 'my-name');
        $sut = new ProfileGroupBuilder('fullName', 'name', $data);
        $sut->setDisplayName('my-name');
    }

    public function testItCanSetTheDisplayDescription(): void
    {
        $data = $this->createMock(ProfileGroupData::class);
        $data->expects($this->once())->method('setAttr')->with('displayDescription', 'my-description');
        $sut = new ProfileGroupBuilder('fullName', 'name', $data);
        $sut->setDisplayDescription('my-description');
    }

    public function testItCanSetARawAttribute(): void
    {
        $data = $this->createMock(ProfileGroupData::class);
        $data->expects($this->once())->method('setAttr')->with('customAttribute', 'my-value');
        $sut = new ProfileGroupBuilder('fullName', 'name', $data);
        $sut->setRawAttribute('customAttribute', 'my-value');
    }

    public function testItCanSetAnAnnotation(): void
    {
        $data = $this->createMock(ProfileGroupData::class);
        $data->expects($this->once())->method('setAttr')->with('annotations', ['annotation' => ['custom' => 'data']]);
        $sut = new ProfileGroupBuilder('fullName', 'name', $data);
        $sut->setAnnotation('annotation', ['custom' => 'data']);
    }

}
