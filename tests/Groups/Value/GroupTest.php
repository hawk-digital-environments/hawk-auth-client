<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Groups\Value;


use Hawk\AuthClient\Groups\Value\Group;
use Hawk\AuthClient\Groups\Value\GroupList;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Group::class)]
class GroupTest extends TestCase
{
    protected GroupList $children;
    protected Group $sut;

    protected function setUp(): void
    {
        $this->children = new GroupList();
        $this->sut = new Group('f47ac10b-58cc-4372-a567-0e02b2c3d001', 'bar', '/bar', $this->children);
    }

    public function testItConstructs(): void
    {

        $this->assertInstanceOf(Group::class, $this->sut);
    }

    public function testItReturnsGivenValues(): void
    {
        $this->assertEquals('f47ac10b-58cc-4372-a567-0e02b2c3d001', $this->sut->getId());
        $this->assertEquals('bar', $this->sut->getName());
        $this->assertEquals('/bar', $this->sut->getPath());
        $this->assertSame($this->children, $this->sut->getChildren());
    }

    public function testItCanStringify(): void
    {
        $this->assertEquals('/bar', (string)$this->sut);
    }

    public function testItCanBeJsonEncoded(): void
    {
        $this->assertJsonStringEqualsJsonString(
            '{"id":"f47ac10b-58cc-4372-a567-0e02b2c3d001","name":"bar","path":"/bar","children":[]}',
            json_encode($this->sut)
        );

    }
}
