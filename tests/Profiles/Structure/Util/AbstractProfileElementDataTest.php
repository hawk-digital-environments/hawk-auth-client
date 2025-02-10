<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Profiles\Structure\Util;


use Hawk\AuthClient\Profiles\Structure\Util\AbstractProfileElementData;
use Hawk\AuthClient\Profiles\Structure\Util\ProfileStructureData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractProfileElementData::class)]
class AbstractProfileElementDataTest extends TestCase
{
    public function testItConstructs(): void
    {
        $this->assertInstanceOf(AbstractProfileElementData::class, $this->createSut());
    }

    public function testItCanGetAttrWithMissingAttrs(): void
    {
        $sut = $this->createSut(
            getAttrs: static fn() => null
        );

        $this->assertNull($sut->getAttr('key'));
    }

    public function testItCanGetAttrWithExistingAttrs(): void
    {
        $sut = $this->createSut(
            getAttrs: static fn() => ['key' => 'value'],
            setAttrs: function (): void {
                $this->fail('setAttrs should not be called');
            }
        );

        $this->assertEquals('value', $sut->getAttr('key'));
    }

    public function testItCanSetAttrWithMissingAttrs(): void
    {
        $calls = 0;
        $sut = $this->createSut(
            getAttrs: static fn() => null,
            setAttrs: function (string $fullName, array $attrs) use (&$calls): void {
                $this->assertEquals('fullName', $fullName);
                $this->assertEquals(['key' => 'value'], $attrs);
                $calls++;
            }
        );

        $sut->setAttr('key', 'value');
        $this->assertEquals(1, $calls);
    }

    public function testItCanSetAttrWithExistingAttrs(): void
    {
        $calls = 0;
        $sut = $this->createSut(
            getAttrs: static fn() => ['key' => 'value'],
            setAttrs: function (string $fullName, array $attrs) use (&$calls): void {
                $this->assertEquals('fullName', $fullName);
                $this->assertEquals(['key' => 'newValue'], $attrs);
                $calls++;
            }
        );

        $sut->setAttr('key', 'newValue');
        $this->assertEquals(1, $calls);
    }

    public function testItWillRemoveAttrIfSetValueIsNull(): void
    {
        $calls = 0;
        $sut = $this->createSut(
            getAttrs: static fn() => ['key' => 'value'],
            setAttrs: function (string $fullName, array $attrs) use (&$calls): void {
                $this->assertEquals('fullName', $fullName);
                $this->assertEquals([], $attrs);
                $calls++;
            }
        );

        $sut->setAttr('key', null);
        $this->assertEquals(1, $calls);
    }

    public function testItDoesNothingSettingANonExistingKeyToNull(): void
    {
        $this->expectNotToPerformAssertions();
        $sut = $this->createSut(
            getAttrs: static fn() => null,
            setAttrs: function (): void {
                $this->fail('setAttrs should not be called');
            }
        );

        $sut->setAttr('key', null);
    }

    public function testItDoesJsonEncode(): void
    {
        $sut = $this->createSut(
            getAttrs: static fn() => ['key' => 'value']
        );

        $this->assertEquals(['key' => 'value'], $sut->jsonSerialize());
    }

    protected function createSut(
        callable|null $getAttrs = null,
        callable|null $setAttrs = null
    ): AbstractProfileElementData
    {
        $i = new class(
            $this->createStub(ProfileStructureData::class),
            'fullName'
        ) extends AbstractProfileElementData {
            public $_getAttrs;
            public $_setAttrs;

            #[\Override] protected function getAttrs(string $fullName): array|null
            {
                return ($this->_getAttrs)($fullName);
            }

            #[\Override] protected function setAttrs(string $fullName, array $attrs): void
            {
                ($this->_setAttrs)($fullName, $attrs);
            }
        };

        if ($getAttrs === null) {
            $getAttrs = static fn() => null;
        }
        if ($setAttrs === null) {
            $setAttrs = static fn() => null;
        }


        $i->_getAttrs = $getAttrs;
        $i->_setAttrs = $setAttrs;

        return $i;
    }

}
