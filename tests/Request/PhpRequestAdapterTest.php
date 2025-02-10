<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Request;


use Hawk\AuthClient\Request\PhpRequestAdapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhpRequestAdapter::class)]
class PhpRequestAdapterTest extends TestCase
{
    protected mixed $getBackup;

    protected function setUp(): void
    {
        $this->getBackup = $_GET ?? null;
    }

    protected function tearDown(): void
    {
        $_GET = $this->getBackup;
    }

    public function testItConstructs(): void
    {
        $sut = new PhpRequestAdapter();
        $this->assertInstanceOf(PhpRequestAdapter::class, $sut);
    }

    public function testItCanGetAKey(): void
    {
        $sut = new PhpRequestAdapter();
        $this->assertNull($sut->getQueryValue('foo'));

        $_GET['foo'] = 'bar';
        $this->assertEquals('bar', $sut->getQueryValue('foo'));
    }
}
