<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\FrontendApi\Handler;


use Hawk\AuthClient\Auth\KeycloakProvider;
use Hawk\AuthClient\FrontendApi\Handler\AuthenticationExchangeCodeForTokenHandler;
use Hawk\AuthClient\FrontendApi\Util\HandlerContext;
use Hawk\AuthClient\FrontendApi\Util\Request;
use Hawk\AuthClient\FrontendApi\Util\ResponseFactory;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AuthenticationExchangeCodeForTokenHandler::class)]
class AuthenticationExchangeCodeForTokenHandlerTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new AuthenticationExchangeCodeForTokenHandler();
        $this->assertInstanceOf(AuthenticationExchangeCodeForTokenHandler::class, $sut);
    }

    public function testItDetectsIfItCanHandle(): void
    {
        $sut = new AuthenticationExchangeCodeForTokenHandler();
        $request = $this->createStub(Request::class);
        $request->method('getRoute')->willReturn(AuthenticationExchangeCodeForTokenHandler::ROUTE);
        $this->assertTrue($sut->canHandle($request));

        $request = $this->createStub(Request::class);
        $request->method('getRoute')->willReturn('auth-exchange-code-for-tokens');
        $this->assertFalse($sut->canHandle($request));
    }

    public function testItReturnsBadRequestIfRedirectUrlIsMissing(): void
    {
        $response = new \stdClass();

        $request = $this->createStub(Request::class);
        $request->method('getPostValue')->willReturnMap([
            ['redirectUrl', null],
            ['code', null],
        ]);

        $responseFactory = $this->createMock(ResponseFactory::class);
        $responseFactory->expects($this->once())
            ->method('buildBadRequest')
            ->with('Missing "redirectUrl" parameter')
            ->willReturn($response);

        $sut = new AuthenticationExchangeCodeForTokenHandler();

        $this->assertSame($response, $sut->handle($request, $responseFactory, $this->createStub(HandlerContext::class)));
    }

    public function testItReturnsBadRequestIfCodeIsMissing(): void
    {
        $response = new \stdClass();

        $request = $this->createStub(Request::class);
        $request->method('getPostValue')->willReturnMap([
            ['redirectUrl', 'http://example.com'],
            ['code', null],
        ]);

        $responseFactory = $this->createMock(ResponseFactory::class);
        $responseFactory->expects($this->once())
            ->method('buildBadRequest')
            ->with('Missing "code" parameter')
            ->willReturn($response);

        $sut = new AuthenticationExchangeCodeForTokenHandler();

        $this->assertSame($response, $sut->handle($request, $responseFactory, $this->createStub(HandlerContext::class)));
    }

    public function testItCanRequestTokenWithoutAdditionalOptions(): void
    {
        $response = new \stdClass();

        $request = $this->createStub(Request::class);
        $request->method('getPostValue')->willReturnMap([
            ['redirectUrl', 'http://example.com'],
            ['code', '123'],
        ]);

        $options = [
            'code' => '123',
            'redirect_uri' => 'http://example.com',
        ];

        $expires = time() + 3600;
        $token = $this->createStub(AccessToken::class);
        $token->method('getToken')->willReturn('token');
        $token->method('getExpires')->willReturn($expires);
        $token->method('getRefreshToken')->willReturn('refreshToken');
        $token->method('getValues')->willReturn(['id_token' => 'bar']);

        $provider = $this->createMock(KeycloakProvider::class);
        $provider->expects($this->once())
            ->method('getAccessToken')
            ->with('authorization_code', $options)
            ->willReturn($token);

        $responseFactory = $this->createMock(ResponseFactory::class);
        $responseFactory->expects($this->once())
            ->method('buildResponse')
            ->with([
                'token' => 'token',
                'refreshToken' => 'refreshToken',
                'idToken' => 'bar',
                'expires' => $expires,
            ])
            ->willReturn($response);

        $context = $this->createStub(HandlerContext::class);
        $context->method('getKeycloakProvider')->willReturn($provider);

        $sut = new AuthenticationExchangeCodeForTokenHandler();

        $this->assertSame($response, $sut->handle($request, $responseFactory, $context));
    }

    public function testItCanRequestTokenWithCodeVerifierOption(): void
    {
        $response = new \stdClass();

        $request = $this->createStub(Request::class);
        $request->method('getPostValue')->willReturnMap([
            ['redirectUrl', 'http://example.com'],
            ['code', '123'],
            ['codeVerifier', 'code-verifier'],
        ]);

        $options = [
            'code' => '123',
            'redirect_uri' => 'http://example.com',
            'code_verifier' => 'code-verifier',
        ];

        $expires = time() + 3600;
        $token = $this->createStub(AccessToken::class);
        $token->method('getToken')->willReturn('token');
        $token->method('getExpires')->willReturn($expires);
        $token->method('getRefreshToken')->willReturn('refreshToken');
        $token->method('getValues')->willReturn(['id_token' => 'bar']);

        $provider = $this->createMock(KeycloakProvider::class);
        $provider->expects($this->once())
            ->method('getAccessToken')
            ->with('authorization_code', $options)
            ->willReturn($token);

        $responseFactory = $this->createMock(ResponseFactory::class);
        $responseFactory->expects($this->once())
            ->method('buildResponse')
            ->with([
                'token' => 'token',
                'refreshToken' => 'refreshToken',
                'idToken' => 'bar',
                'expires' => $expires,
            ])
            ->willReturn($response);

        $context = $this->createStub(HandlerContext::class);
        $context->method('getKeycloakProvider')->willReturn($provider);

        $sut = new AuthenticationExchangeCodeForTokenHandler();

        $this->assertSame($response, $sut->handle($request, $responseFactory, $context));
    }

    public function testItReturnsBadRequestIfTokenRequestFails(): void
    {
        $response = new \stdClass();

        $request = $this->createStub(Request::class);
        $request->method('getPostValue')->willReturnMap([
            ['redirectUrl', 'http://example.com'],
            ['code', '123'],
        ]);

        $responseFactory = $this->createMock(ResponseFactory::class);
        $responseFactory->expects($this->once())
            ->method('buildBadRequest')
            ->with('Failed to request token')
            ->willReturn($response);

        $context = $this->createStub(HandlerContext::class);
        $context->method('getKeycloakProvider')->willThrowException(new \Exception('Failed to request token'));

        $sut = new AuthenticationExchangeCodeForTokenHandler();

        $this->assertSame($response, $sut->handle($request, $responseFactory, $context));
    }
}
