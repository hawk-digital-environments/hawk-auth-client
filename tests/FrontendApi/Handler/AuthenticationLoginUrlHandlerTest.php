<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\FrontendApi\Handler;


use Hawk\AuthClient\Auth\KeycloakProvider;
use Hawk\AuthClient\FrontendApi\Handler\AuthenticationLoginUrlHandler;
use Hawk\AuthClient\FrontendApi\Util\HandlerContext;
use Hawk\AuthClient\FrontendApi\Util\Request;
use Hawk\AuthClient\FrontendApi\Util\ResponseFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AuthenticationLoginUrlHandler::class)]
class AuthenticationLoginUrlHandlerTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new AuthenticationLoginUrlHandler();
        $this->assertInstanceOf(AuthenticationLoginUrlHandler::class, $sut);
    }

    public function testItDetectsIfItCanHandle(): void
    {
        $sut = new AuthenticationLoginUrlHandler();
        $request = $this->createStub(Request::class);
        $request->method('getRoute')->willReturn(AuthenticationLoginUrlHandler::ROUTE);
        $this->assertTrue($sut->canHandle($request));

        $request = $this->createStub(Request::class);
        $request->method('getRoute')->willReturn('auth-login-urls');
        $this->assertFalse($sut->canHandle($request));
    }

    public function testItReturnsBadRequestIfRedirectUrlIsMissing(): void
    {
        $response = new \stdClass();

        $request = $this->createStub(Request::class);
        $request->method('getPostValue')->willReturn(null);

        $responseFactory = $this->createMock(ResponseFactory::class);
        $responseFactory->expects($this->once())
            ->method('buildBadRequest')
            ->with('Missing "redirectUrl" parameter')
            ->willReturn($response);

        $sut = new AuthenticationLoginUrlHandler();

        $this->assertSame($response, $sut->handle($request, $responseFactory, $this->createStub(HandlerContext::class)));
    }

    public function testItReturnsUrlWithoutAdditionalOptions(): void
    {
        $response = new \stdClass();

        $request = $this->createStub(Request::class);
        $request->method('getPostValue')->willReturnMap([
            ['redirectUrl', 'http://example.com'],
            ['state', null],
            ['codeChallenge', null],
        ]);

        $options = [
            'redirect_uri' => 'http://example.com',
        ];

        $url = 'http://login.example.com';
        $provider = $this->createMock(KeycloakProvider::class);
        $provider->expects($this->once())->method('getAuthorizationUrl')->with($options)->willReturn($url);

        $responseFactory = $this->createMock(ResponseFactory::class);
        $responseFactory->expects($this->once())
            ->method('buildResponse')
            ->with(['url' => $url])
            ->willReturn($response);

        $context = $this->createStub(HandlerContext::class);
        $context->method('getKeycloakProvider')->willReturn($provider);

        $sut = new AuthenticationLoginUrlHandler();
        $this->assertSame($response, $sut->handle($request, $responseFactory, $context));
    }

    public function testItReturnsUrlWithStateOption(): void
    {
        $response = new \stdClass();

        $request = $this->createStub(Request::class);
        $request->method('getPostValue')->willReturnMap([
            ['redirectUrl', 'http://example.com'],
            ['state', 'state'],
            ['codeChallenge', null],
        ]);

        $options = [
            'redirect_uri' => 'http://example.com',
            'state' => 'state',
        ];

        $url = 'http://login.example.com';
        $provider = $this->createMock(KeycloakProvider::class);
        $provider->expects($this->once())->method('getAuthorizationUrl')->with($options)->willReturn($url);

        $responseFactory = $this->createMock(ResponseFactory::class);
        $responseFactory->expects($this->once())
            ->method('buildResponse')
            ->with(['url' => $url])
            ->willReturn($response);

        $context = $this->createStub(HandlerContext::class);
        $context->method('getKeycloakProvider')->willReturn($provider);

        $sut = new AuthenticationLoginUrlHandler();
        $this->assertSame($response, $sut->handle($request, $responseFactory, $context));
    }

    public function testItReturnsUrlWithCodeChallengeOption(): void
    {
        $response = new \stdClass();

        $request = $this->createStub(Request::class);
        $request->method('getPostValue')->willReturnMap([
            ['redirectUrl', 'http://example.com'],
            ['state', null],
            ['codeChallenge', 'code-challenge'],
        ]);

        $options = [
            'redirect_uri' => 'http://example.com',
            'code_challenge' => 'code-challenge',
            'code_challenge_method' => 'S256',
        ];

        $url = 'http://login.example.com';
        $provider = $this->createMock(KeycloakProvider::class);
        $provider->expects($this->once())->method('getAuthorizationUrl')->with($options)->willReturn($url);

        $responseFactory = $this->createMock(ResponseFactory::class);
        $responseFactory->expects($this->once())
            ->method('buildResponse')
            ->with(['url' => $url])
            ->willReturn($response);

        $context = $this->createStub(HandlerContext::class);
        $context->method('getKeycloakProvider')->willReturn($provider);

        $sut = new AuthenticationLoginUrlHandler();
        $this->assertSame($response, $sut->handle($request, $responseFactory, $context));
    }

    public function testItReturnsUrlWithAllOptions(): void
    {
        $response = new \stdClass();

        $request = $this->createStub(Request::class);
        $request->method('getPostValue')->willReturnMap([
            ['redirectUrl', 'http://example.com'],
            ['state', 'state'],
            ['codeChallenge', 'code-challenge'],
        ]);

        $options = [
            'redirect_uri' => 'http://example.com',
            'state' => 'state',
            'code_challenge' => 'code-challenge',
            'code_challenge_method' => 'S256',
        ];

        $url = 'http://login.example.com';
        $provider = $this->createMock(KeycloakProvider::class);
        $provider->expects($this->once())->method('getAuthorizationUrl')->with($options)->willReturn($url);

        $responseFactory = $this->createMock(ResponseFactory::class);
        $responseFactory->expects($this->once())
            ->method('buildResponse')
            ->with(['url' => $url])
            ->willReturn($response);

        $context = $this->createStub(HandlerContext::class);
        $context->method('getKeycloakProvider')->willReturn($provider);

        $sut = new AuthenticationLoginUrlHandler();
        $this->assertSame($response, $sut->handle($request, $responseFactory, $context));
    }
}
