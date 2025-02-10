<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Profiles\Structure\Util;


use Hawk\AuthClient\Profiles\Structure\Util\ProfileFieldData;
use Hawk\AuthClient\Profiles\Structure\Util\ProfileStructureData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProfileFieldData::class)]
class ProfileFieldDataTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new ProfileFieldData(
            $this->createStub(ProfileStructureData::class),
            'baseKey'
        );
        $this->assertInstanceOf(ProfileFieldData::class, $sut);
    }

    public function testItGetsAttrs(): void
    {
        $data = $this->createMock(ProfileStructureData::class);
        $data->expects($this->once())
            ->method('getField')
            ->with('baseKey')
            ->willReturn(['key' => 'value']);

        $sut = new ProfileFieldData($data, 'baseKey');
        $this->assertEquals('value', $sut->getAttr('key'));
    }

    public function testItSetsAttrs(): void
    {
        $data = $this->createMock(ProfileStructureData::class);
        $data->expects($this->once())
            ->method('setField')
            ->with('baseKey', ['key' => 'newValue']);

        $sut = new ProfileFieldData($data, 'baseKey');
        $sut->setAttr('key', 'newValue');
    }
}
