<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Profiles\Structure\Util;


use Hawk\AuthClient\Profiles\Structure\Util\ProfileGroupData;
use Hawk\AuthClient\Profiles\Structure\Util\ProfileStructureData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProfileGroupData::class)]
class ProfileGroupDataTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new ProfileGroupData(
            $this->createStub(ProfileStructureData::class),
            'baseKey'
        );
        $this->assertInstanceOf(ProfileGroupData::class, $sut);
    }

    public function testItGetsAttrs(): void
    {
        $data = $this->createMock(ProfileStructureData::class);
        $data->expects($this->once())
            ->method('getGroup')
            ->with('baseKey')
            ->willReturn(['key' => 'value']);

        $sut = new ProfileGroupData($data, 'baseKey');
        $this->assertEquals('value', $sut->getAttr('key'));
    }

    public function testItSetsAttrs(): void
    {
        $data = $this->createMock(ProfileStructureData::class);
        $data->expects($this->once())
            ->method('setGroup')
            ->with('baseKey', ['key' => 'newValue']);

        $sut = new ProfileGroupData($data, 'baseKey');
        $sut->setAttr('key', 'newValue');
    }
}
