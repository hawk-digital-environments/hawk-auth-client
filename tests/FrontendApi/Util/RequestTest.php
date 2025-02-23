<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\FrontendApi\Util;


use Hawk\AuthClient\FrontendApi\Util\Request;
use Hawk\AuthClient\Request\RequestAdapterInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Request::class)]
class RequestTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new Request('route', $this->createStub(RequestAdapterInterface::class));
        $this->assertInstanceOf(Request::class, $sut);
    }

    public function testItReturnsTheGivenRoute(): void
    {
        $sut = new Request('route', $this->createStub(RequestAdapterInterface::class));
        $this->assertEquals('route', $sut->getRoute());
    }

    public static function provideTestItCanReturnTheBearerTokenData(): iterable
    {
        yield 'correct token' => ['Bearer token', 'token'];
        yield 'empty token' => ['Bearer ', null];
        yield 'missing token' => [null, null];
        yield 'missing bearer prefix' => ['token', 'token'];
    }

    #[DataProvider('provideTestItCanReturnTheBearerTokenData')]
    public function testItCanReturnTheBearerToken(mixed $headerValue, string|null $expectedValue): void
    {
        $requestAdapter = $this->createStub(RequestAdapterInterface::class);
        $requestAdapter->method('getHeaderValue')->willReturn($headerValue);

        $sut = new Request('', $requestAdapter);

        $this->assertEquals($expectedValue, $sut->getBearerToken());
    }

    public function testItCanProxyRootRequestMethods(): void
    {
        $requestAdapter = $this->createMock(RequestAdapterInterface::class);
        $requestAdapter->expects($this->once())->method('getQueryValue')->with('key')->willReturn('value');
        $requestAdapter->expects($this->once())->method('getHeaderValue')->with('key')->willReturn('value');
        $requestAdapter->expects($this->once())->method('getPostValue')->with('key')->willReturn('value');

        $sut = new Request('', $requestAdapter);

        $this->assertEquals('value', $sut->getQueryValue('key'));
        $this->assertEquals('value', $sut->getHeaderValue('key'));
        $this->assertEquals('value', $sut->getPostValue('key'));
    }

}
