<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Profiles\Structure\Util;


use Hawk\AuthClient\Profiles\Structure\Util\AbstractProfileElementData;
use Hawk\AuthClient\Profiles\Structure\Util\AssocListBasedValue;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AssocListBasedValue::class)]
class AssocListBasedValueTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new AssocListBasedValue(
            $this->createStub(AbstractProfileElementData::class),
            'baseKey'
        );
        $this->assertInstanceOf(AssocListBasedValue::class, $sut);
    }

    public function testItCanGetAValueWhenTheBaseKeyExists(): void
    {
        $data = $this->createMock(AbstractProfileElementData::class);
        $data->expects($this->once())
            ->method('getAttr')
            ->with('baseKey')
            ->willReturn(['key' => 'value']);
        $sut = new AssocListBasedValue($data, 'baseKey');
        $this->assertEquals('value', $sut->getValue('key'));
    }

    public function testItCanGetAValueWhenTheBaseKeyIsMissing(): void
    {
        $data = $this->createMock(AbstractProfileElementData::class);
        $data->expects($this->once())
            ->method('getAttr')
            ->with('baseKey')
            ->willReturn(null);
        $sut = new AssocListBasedValue($data, 'baseKey');
        $this->assertNull($sut->getValue('key'));

    }

    public function testItCanSetAValueWhenTheBaseKeyExists(): void
    {
        $data = $this->createMock(AbstractProfileElementData::class);
        $data->expects($this->once())
            ->method('getAttr')
            ->with('baseKey')
            ->willReturn(['key' => 'value']);
        $data->expects($this->once())
            ->method('setAttr')
            ->with('baseKey', ['key' => 'newValue']);
        $sut = new AssocListBasedValue($data, 'baseKey');
        $sut->setValue('key', 'newValue');
    }

    public function testItCanSetAValueWhenTheBaseKeyIsMissing(): void
    {
        $data = $this->createMock(AbstractProfileElementData::class);
        $data->expects($this->once())
            ->method('getAttr')
            ->with('baseKey')
            ->willReturn(null);
        $data->expects($this->once())
            ->method('setAttr')
            ->with('baseKey', ['key' => 'value']);
        $sut = new AssocListBasedValue($data, 'baseKey');
        $sut->setValue('key', 'value');
    }

    public function testRemovingTheLastElementInTheBaseKeyArrayRemovesTheBaseKeyArrayBySettingItNull(): void
    {
        $data = $this->createMock(AbstractProfileElementData::class);
        $data->expects($this->once())
            ->method('getAttr')
            ->with('baseKey')
            ->willReturn(['key' => 'value']);
        $data->expects($this->once())
            ->method('setAttr')
            ->with('baseKey', null);
        $sut = new AssocListBasedValue($data, 'baseKey');
        $sut->setValue('key', null);
    }

    public function testSettingAValueSortsTheElementsInTheBaseKey(): void
    {
        $data = $this->createMock(AbstractProfileElementData::class);
        $data->expects($this->once())
            ->method('getAttr')
            ->with('baseKey')
            ->willReturn(['b' => 'value-b', 'a' => 'value-a']);
        $data->expects($this->once())
            ->method('setAttr')
            ->with('baseKey', ['a' => 'value-a', 'b' => 'value-b', 'c' => 'value']);
        $sut = new AssocListBasedValue($data, 'baseKey');
        $sut->setValue('c', 'value');
    }
}
