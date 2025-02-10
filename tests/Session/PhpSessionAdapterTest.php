<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Session;

use Hawk\AuthClient\Exception\SessionIsNotStartedException;
use Hawk\AuthClient\Session\PhpSessionAdapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhpSessionAdapter::class)]
#[CoversClass(SessionIsNotStartedException::class)]
class PhpSessionAdapterTest extends TestCase
{
    private mixed $sessionBackup;

    public function testItConstructs(): void
    {
        $sut = new PhpSessionAdapter();
        $this->assertInstanceOf(PhpSessionAdapter::class, $sut);
    }

    protected function setUp(): void
    {
        $this->sessionBackup = $_SESSION ?? null;
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = $this->sessionBackup;
    }

    public function testItFailsIfSessionIsNotStarted(): void
    {
        $this->expectException(SessionIsNotStartedException::class);
        $sut = new PhpSessionAdapter();
        $sut->get('foo');
    }

    public function testItCanSetAndGetSessionValues(): void
    {
        $sut = new PhpSessionAdapter(false);
        $this->assertFalse($sut->has('foo'));
        $this->assertNull($sut->get('foo'));
        $sut->set('foo', 'bar');
        $this->assertTrue($sut->has('foo'));
        $this->assertEquals('bar', $sut->get('foo'));
        $this->assertEquals('bar', $_SESSION[PhpSessionAdapter::SESSION_NAMESPACE]['foo']);
        $sut->remove('foo');
        $this->assertFalse($sut->has('foo'));
        $this->assertNull($sut->get('foo'));
    }
}
