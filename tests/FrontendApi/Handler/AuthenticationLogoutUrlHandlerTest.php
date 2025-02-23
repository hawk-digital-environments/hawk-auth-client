<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\FrontendApi\Handler;


use Hawk\AuthClient\Auth\KeycloakProvider;
use Hawk\AuthClient\FrontendApi\Handler\AuthenticationLogoutUrlHandler;
use Hawk\AuthClient\FrontendApi\Util\HandlerContext;
use Hawk\AuthClient\FrontendApi\Util\Request;
use Hawk\AuthClient\FrontendApi\Util\ResponseFactory;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AuthenticationLogoutUrlHandler::class)]
class AuthenticationLogoutUrlHandlerTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new AuthenticationLogoutUrlHandler();
        $this->assertInstanceOf(AuthenticationLogoutUrlHandler::class, $sut);
    }

    public function testItDetectsIfItCanHandle(): void
    {
        $sut = new AuthenticationLogoutUrlHandler();
        $request = $this->createStub(Request::class);
        $request->method('getRoute')->willReturn(AuthenticationLogoutUrlHandler::ROUTE);
        $this->assertTrue($sut->canHandle($request));

        $request = $this->createStub(Request::class);
        $request->method('getRoute')->willReturn('auth-logout-urls');
        $this->assertFalse($sut->canHandle($request));
    }

    public function testItReturnsBadRequestIfRedirectUrlIsMissing(): void
    {
        $response = new \stdClass();

        $request = $this->createStub(Request::class);
        $request->method('getPostValue')->willReturnMap([
            ['redirectUrl', null],
            ['idToken', 'idToken'],
        ]);

        $responseFactory = $this->createMock(ResponseFactory::class);
        $responseFactory->expects($this->once())
            ->method('buildBadRequest')
            ->with('Missing "redirectUrl" parameter')
            ->willReturn($response);

        $sut = new AuthenticationLogoutUrlHandler();

        $this->assertSame($response, $sut->handle($request, $responseFactory, $this->createStub(HandlerContext::class)));
    }

    public function testItReturnsBadRequestIfIdTokenIsMissing(): void
    {
        $response = new \stdClass();

        $request = $this->createStub(Request::class);
        $request->method('getPostValue')->willReturnMap([
            ['redirectUrl', 'https://example.com'],
            ['idToken', null],
        ]);

        $responseFactory = $this->createMock(ResponseFactory::class);
        $responseFactory->expects($this->once())
            ->method('buildBadRequest')
            ->with('Missing "idToken" parameter')
            ->willReturn($response);

        $sut = new AuthenticationLogoutUrlHandler();

        $this->assertSame($response, $sut->handle($request, $responseFactory, $this->createStub(HandlerContext::class)));
    }

    public function testItReturnsLogoutUrl(): void
    {
        $response = new \stdClass();

        $request = $this->createStub(Request::class);
        $request->method('getPostValue')->willReturnMap([
            ['redirectUrl', 'https://example.com'],
            ['idToken', 'idToken'],
        ]);

        $provider = $this->createMock(KeycloakProvider::class);
        $provider->expects($this->once())->method('getLogoutUrl')->with($this->callback(function (AccessToken $token) {
            $this->assertEquals('idToken', $token->getToken());
            $this->assertEquals('idToken', $token->getValues()['id_token']);
            return true;
        }), 'https://example.com')
            ->willReturn('https://logout.example.com/');

        $responseFactory = $this->createMock(ResponseFactory::class);
        $responseFactory->expects($this->once())
            ->method('buildResponse')
            ->with([
                'url' => 'https://logout.example.com/',
            ])
            ->willReturn($response);

        $context = $this->createStub(HandlerContext::class);
        $context->method('getKeycloakProvider')->willReturn($provider);

        $sut = new AuthenticationLogoutUrlHandler();

        $this->assertSame($response, $sut->handle($request, $responseFactory, $context));
    }
}
