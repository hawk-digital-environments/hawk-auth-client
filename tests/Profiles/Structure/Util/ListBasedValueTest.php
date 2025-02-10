<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Profiles\Structure\Util;


use Hawk\AuthClient\Profiles\Structure\Util\AbstractProfileElementData;
use Hawk\AuthClient\Profiles\Structure\Util\ListBasedValue;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ListBasedValue::class)]
class ListBasedValueTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new ListBasedValue($this->createStub(AbstractProfileElementData::class), 'baseKey');
        $this->assertInstanceOf(ListBasedValue::class, $sut);
    }

    public function testItCanCheckIfAnItemIsInAListIfTheBaseKeyDoesNotExist(): void
    {
        $data = $this->createStub(AbstractProfileElementData::class);
        $data->method('getAttr')->willReturn(null);
        $sut = new ListBasedValue($data, 'baseKey');
        $this->assertFalse($sut->checkIfInList('foo', 'list'));
    }

    public function testItCanCheckIfAnItemIsInAListIfTheListKeyDoesNotExist(): void
    {
        $data = $this->createStub(AbstractProfileElementData::class);
        $data->method('getAttr')
            ->willReturn([]);
        $sut = new ListBasedValue($data, 'baseKey');
        $this->assertFalse($sut->checkIfInList('foo', 'list'));
    }

    public function testItCanCheckIfAnItemIsInAListIfBothListAndBaseKeyExist(): void
    {
        $data = $this->createStub(AbstractProfileElementData::class);
        $data->method('getAttr')
            ->willReturn(['list' => ['foo', 'bar', 'baz']]);
        $sut = new ListBasedValue($data, 'baseKey');
        $this->assertTrue($sut->checkIfInList('foo', 'list'));
        $this->assertFalse($sut->checkIfInList('qux', 'list'));
    }

    public function testItCanAddAValueToTheListIfTheBaseKeyDoesNotExist(): void
    {
        $data = $this->createMock(AbstractProfileElementData::class);
        $data->expects($this->exactly(2))->method('getAttr')->with('baseKey')->willReturn(null);
        $data->expects($this->once())->method('setAttr')->with('baseKey', ['list' => ['foo']]);
        $sut = new ListBasedValue($data, 'baseKey');
        $sut->addToList('foo', 'list');
    }

    public function testItCanAddAValueToTheListIfTheListKeyDoesNotExist(): void
    {
        $data = $this->createMock(AbstractProfileElementData::class);
        $data->expects($this->exactly(2))->method('getAttr')->with('baseKey')->willReturn(['foo' => false]);
        $data->expects($this->once())->method('setAttr')->with('baseKey', ['list' => ['foo'], 'foo' => false]);
        $sut = new ListBasedValue($data, 'baseKey');
        $sut->addToList('foo', 'list');
    }

    public function testItCanAddAValueToTheListIfBothListAndBaseKeyExists(): void
    {

        $data = $this->createMock(AbstractProfileElementData::class);
        $data->expects($this->exactly(2))->method('getAttr')->with('baseKey')->willReturn(['list' => ['foo', 'bar']]);
        $data->expects($this->once())->method('setAttr')->with('baseKey', ['list' => ['foo', 'bar', 'baz']]);
        $sut = new ListBasedValue($data, 'baseKey');
        $sut->addToList('baz', 'list');
    }

    public function testItDoesNothingOnAddAValueToTheListIfValueAlreadyExists(): void
    {
        $data = $this->createMock(AbstractProfileElementData::class);
        $data->expects($this->once())->method('getAttr')->with('baseKey')->willReturn(['list' => ['foo', 'bar']]);
        $data->expects($this->never())->method('setAttr');
        $sut = new ListBasedValue($data, 'baseKey');
        $sut->addToList('foo', 'list');
    }

    public function testItCanRemoveAValueFromTheListIfTheBaseKeyDoesNotExist(): void
    {
        $data = $this->createMock(AbstractProfileElementData::class);
        $data->expects($this->once())->method('getAttr')->with('baseKey')->willReturn(null);
        $data->expects($this->never())->method('setAttr');
        $sut = new ListBasedValue($data, 'baseKey');
        $sut->removeFromList('foo', 'list');

    }

    public function testItCanRemoveAValueFromTheListIfTheListKeyDoesNotExist(): void
    {
        $data = $this->createMock(AbstractProfileElementData::class);
        $data->expects($this->once())->method('getAttr')->with('baseKey')->willReturn(['foo' => false]);
        $data->expects($this->never())->method('setAttr');
        $sut = new ListBasedValue($data, 'baseKey');
        $sut->removeFromList('foo', 'list');
    }

    public function testItCanRemoveAValueFromTheListIfBothListAndBaseKeyExists(): void
    {
        $data = $this->createMock(AbstractProfileElementData::class);
        $data->expects($this->exactly(2))->method('getAttr')->with('baseKey')->willReturn(['list' => ['foo', 'bar']]);
        $data->expects($this->once())->method('setAttr')->with('baseKey', ['list' => ['foo']]);
        $sut = new ListBasedValue($data, 'baseKey');
        $sut->removeFromList('bar', 'list');
    }

    public function testItDoesNothingIfTheValueToRemoveDoesNotExist(): void
    {
        $data = $this->createMock(AbstractProfileElementData::class);
        $data->expects($this->once())->method('getAttr')->with('baseKey')->willReturn(['list' => ['foo', 'bar']]);
        $data->expects($this->never())->method('setAttr');
        $sut = new ListBasedValue($data, 'baseKey');
        $sut->removeFromList('baz', 'list');
    }

    public function testItDoesNothingIfTheListToRemoveFromIsEmpty(): void
    {

        $data = $this->createMock(AbstractProfileElementData::class);
        $data->expects($this->once())->method('getAttr')->with('baseKey')->willReturn(['list' => []]);
        $data->expects($this->never())->method('setAttr');
        $sut = new ListBasedValue($data, 'baseKey');
        $sut->removeFromList('baz', 'list');
    }

    public function testItRemovesTheListIfTheLastItemOfTheListWasRemoved(): void
    {
        $data = $this->createMock(AbstractProfileElementData::class);
        $data->expects($this->exactly(2))->method('getAttr')->with('baseKey')->willReturn(['list' => ['bar']]);
        $data->expects($this->once())->method('setAttr')->with('baseKey', []);
        $sut = new ListBasedValue($data, 'baseKey');
        $sut->removeFromList('bar', 'list');
    }

    public function testItCanToggleAValueInListIfTheBaseKeyDoesNotExist(): void
    {
        $data = $this->createMock(AbstractProfileElementData::class);
        $data->expects($this->exactly(3))->method('getAttr')->with('baseKey')->willReturn(null);
        $data->expects($this->once())->method('setAttr')->with('baseKey', ['list' => ['foo']]);
        $sut = new ListBasedValue($data, 'baseKey');
        // Two read, one write
        $sut->toggleInList(true, 'foo', 'list');
        // One read, no write
        $sut->toggleInList(false, 'foo', 'list');
    }

    public function testItCanToggleAValueIfTheListKeyDoesNotExist(): void
    {
        $data = $this->createMock(AbstractProfileElementData::class);
        $data->expects($this->exactly(3))->method('getAttr')->with('baseKey')->willReturn(['foo' => false]);
        $data->expects($this->once())->method('setAttr')->with('baseKey', ['list' => ['foo'], 'foo' => false]);
        $sut = new ListBasedValue($data, 'baseKey');
        // Two read, one write
        $sut->toggleInList(true, 'foo', 'list');
        // One read, no write
        $sut->toggleInList(false, 'foo', 'list');
    }

    public function testItCanToggleAValueOnIfBothListAndBaseKeyExist(): void
    {
        $data = $this->createMock(AbstractProfileElementData::class);
        $data->expects($this->exactly(2))->method('getAttr')->with('baseKey')->willReturn(['list' => ['bar']]);
        $data->expects($this->once())->method('setAttr')->with('baseKey', ['list' => ['bar', 'foo']]);
        $sut = new ListBasedValue($data, 'baseKey');
        $sut->toggleInList(true, 'foo', 'list');
    }

    public function testItCanToggleAValueOffIfBothListAndBaseKeyExist(): void
    {
        $data = $this->createMock(AbstractProfileElementData::class);
        $data->expects($this->exactly(2))->method('getAttr')->with('baseKey')->willReturn(['list' => ['foo']]);
        $data->expects($this->once())->method('setAttr')->with('baseKey', []);
        $sut = new ListBasedValue($data, 'baseKey');
        $sut->toggleInList(false, 'foo', 'list');
    }

    public function testItDoesNothingTryingToToggleOnAValueAlreadyInTheList(): void
    {
        $data = $this->createMock(AbstractProfileElementData::class);
        $data->expects($this->once())->method('getAttr')->with('baseKey')->willReturn(['list' => ['foo']]);
        $data->expects($this->never())->method('setAttr');
        $sut = new ListBasedValue($data, 'baseKey');
        $sut->toggleInList(true, 'foo', 'list');
    }

    public function testItDoesNothingTryingToToggleOffAValueNotInTheList(): void
    {
        $data = $this->createMock(AbstractProfileElementData::class);
        $data->expects($this->once())->method('getAttr')->with('baseKey')->willReturn(['list' => ['bar']]);
        $data->expects($this->never())->method('setAttr');
        $sut = new ListBasedValue($data, 'baseKey');
        $sut->toggleInList(false, 'foo', 'list');
    }

    public function testItReturnsNothingIfBaseKeyIsMissing(): void
    {
        $data = $this->createMock(AbstractProfileElementData::class);
        $data->expects($this->once())->method('getAttr')->with('baseKey')->willReturn(null);
        $sut = new ListBasedValue($data, 'baseKey');
        $this->assertEquals([], $sut->getList('list'));
    }

    public function testItReturnsAnEmptyListIfListKeyIsMissing(): void
    {
        $data = $this->createMock(AbstractProfileElementData::class);
        $data->expects($this->once())->method('getAttr')->with('baseKey')->willReturn(['foo' => true]);
        $sut = new ListBasedValue($data, 'baseKey');
        $this->assertEquals([], $sut->getList('list'));
    }

    public function testItCanReturnAList(): void
    {
        $data = $this->createMock(AbstractProfileElementData::class);
        $data->expects($this->once())->method('getAttr')->with('baseKey')->willReturn(['foo' => true, 'list' => ['foo', 'bar']]);
        $sut = new ListBasedValue($data, 'baseKey');
        $this->assertEquals(['foo', 'bar'], $sut->getList('list'));
    }

    public function testItCanSetAListIfBaseKeyIsMissing(): void
    {
        $data = $this->createMock(AbstractProfileElementData::class);
        $data->expects($this->once())->method('getAttr')->with('baseKey')->willReturn(null);
        $data->expects($this->once())->method('setAttr')->with('baseKey', ['list' => ['foo', 'bar']]);
        $sut = new ListBasedValue($data, 'baseKey');
        $sut->setList(['foo', 'bar'], 'list');
    }

    public function testItCanSetAListIfListKeyIsMissing(): void
    {
        $data = $this->createMock(AbstractProfileElementData::class);
        $data->expects($this->once())->method('getAttr')->with('baseKey')->willReturn(['foo' => true]);
        $data->expects($this->once())->method('setAttr')->with('baseKey', ['list' => ['foo', 'bar'], 'foo' => true]);
        $sut = new ListBasedValue($data, 'baseKey');
        $sut->setList(['foo', 'bar', 'bar', 'foo'], 'list'); // Bonus-test: Values are unique
    }

    public function testItCanSetAListIfBothListAndBaseKeyExist(): void
    {
        $data = $this->createMock(AbstractProfileElementData::class);
        $data->expects($this->once())->method('getAttr')->with('baseKey')->willReturn(['list' => ['faz']]);
        $data->expects($this->once())->method('setAttr')->with('baseKey', ['list' => ['foo', 'bar']]);
        $sut = new ListBasedValue($data, 'baseKey');
        $sut->setList(['foo', 'bar'], 'list');
    }

    public function testItRemovesTheListKeyIfSettingAnEmptyList(): void
    {
        $data = $this->createMock(AbstractProfileElementData::class);
        $data->expects($this->once())->method('getAttr')->with('baseKey')->willReturn(['list' => ['faz'], 'foo' => true]);
        $data->expects($this->once())->method('setAttr')->with('baseKey', ['foo' => true]);
        $sut = new ListBasedValue($data, 'baseKey');
        $sut->setList([], 'list');
    }
}
