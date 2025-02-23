<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Request;


use Hawk\AuthClient\Request\PhpRequestAdapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhpRequestAdapter::class)]
class PhpRequestAdapterTest extends TestCase
{
    protected mixed $getBackup;
    protected mixed $postBackup;
    protected mixed $serverBackup;

    protected function setUp(): void
    {
        $this->getBackup = $_GET ?? null;
        $_GET = [];
        $this->postBackup = $_POST ?? null;
        $_POST = [];
        $this->serverBackup = $_SERVER ?? null;
        $_SERVER = [];
    }

    protected function tearDown(): void
    {
        $_GET = $this->getBackup;
        $_POST = $this->postBackup;
        $_SERVER = $this->serverBackup;
    }

    public function testItConstructs(): void
    {
        $sut = new PhpRequestAdapter();
        $this->assertInstanceOf(PhpRequestAdapter::class, $sut);
    }

    public function testItCanGetAQueryValue(): void
    {
        $sut = new PhpRequestAdapter();
        $this->assertNull($sut->getQueryValue('foo'));

        $_GET['foo'] = 'bar';
        $this->assertEquals('bar', $sut->getQueryValue('foo'));
    }

    public static function provideTestItCompliesToTheStringOnlyValueContractData(): iterable
    {
        yield 'null' => [null, null];
        yield 'empty string' => ['', ''];
        yield 'string' => ['foo', 'foo'];
        yield 'array' => [['foo'], 'foo'];
        yield 'int' => [1, '1'];
        yield 'float' => [1.1, '1.1'];
        yield 'bool' => [true, '1'];
    }

    #[DataProvider('provideTestItCompliesToTheStringOnlyValueContractData')]
    public function testItCompliesToTheStringOnlyValueContract(mixed $value, string|null $expected): void
    {
        $sut = new PhpRequestAdapter();
        $_GET['foo'] = $value;
        $this->assertEquals($expected, $sut->getQueryValue('foo'));
    }

    public function testItCanGetAPostValue(): void
    {
        $sut = new PhpRequestAdapter();
        $this->assertNull($sut->getPostValue('foo'));

        $_POST['foo'] = 'bar';
        $this->assertEquals('bar', $sut->getPostValue('foo'));
    }

    public function testItCanGetAHeaderValue(): void
    {
        $sut = new PhpRequestAdapter();
        $this->assertNull($sut->getHeaderValue('foo'));

        $_SERVER['HTTP_FOO'] = 'bar';
        $this->assertEquals('bar', $sut->getHeaderValue('foo'));
    }
}
