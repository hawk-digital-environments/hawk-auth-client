<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\FrontendApi\Handler;


use Hawk\AuthClient\Auth\KeycloakProvider;
use Hawk\AuthClient\FrontendApi\Handler\AuthenticationRefreshTokenHandler;
use Hawk\AuthClient\FrontendApi\Util\HandlerContext;
use Hawk\AuthClient\FrontendApi\Util\Request;
use Hawk\AuthClient\FrontendApi\Util\ResponseFactory;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AuthenticationRefreshTokenHandler::class)]
class AuthenticationRefreshTokenHandlerTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new AuthenticationRefreshTokenHandler();
        $this->assertInstanceOf(AuthenticationRefreshTokenHandler::class, $sut);
    }

    public function testItDetectsIfItCanHandle(): void
    {
        $sut = new AuthenticationRefreshTokenHandler();
        $request = $this->createStub(Request::class);
        $request->method('getRoute')->willReturn(AuthenticationRefreshTokenHandler::ROUTE);
        $this->assertTrue($sut->canHandle($request));

        $request = $this->createStub(Request::class);
        $request->method('getRoute')->willReturn('auth-refresh-tokens');
        $this->assertFalse($sut->canHandle($request));
    }

    public function testItReturnsBadRequestIfNoRefreshTokenWasGiven(): void
    {
        $response = new \stdClass();

        $request = $this->createStub(Request::class);
        $request->method('getPostValue')->willReturn(null);

        $responseFactory = $this->createMock(ResponseFactory::class);
        $responseFactory->expects($this->once())
            ->method('buildBadRequest')
            ->with('Missing "refreshToken" parameter')
            ->willReturn($response);

        $context = $this->createMock(HandlerContext::class);
        $context->expects($this->never())->method('getKeycloakProvider');

        $sut = new AuthenticationRefreshTokenHandler();
        $this->assertSame($response, $sut->handle($request, $responseFactory, $context));
    }

    public function testItReturnsNewToken(): void
    {
        $expires = time() + 3600;
        $newToken = $this->createStub(AccessToken::class);
        $newToken->method('getToken')->willReturn('newToken');
        $newToken->method('getExpires')->willReturn($expires);
        $newToken->method('getRefreshToken')->willReturn('newRefreshToken');
        $newToken->method('getValues')->willReturn(['foo' => 'bar']);

        $response = new \stdClass();

        $request = $this->createStub(Request::class);
        $request->method('getPostValue')->willReturn('refreshToken');

        $provider = $this->createMock(KeycloakProvider::class);
        $provider->expects($this->once())
            ->method('getAccessToken')
            ->with('refresh_token', ['refresh_token' => 'refreshToken'])
            ->willReturn($newToken);

        $responseFactory = $this->createMock(ResponseFactory::class);
        $responseFactory->expects($this->once())
            ->method('buildResponse')
            ->with([
                'token' => 'newToken',
                'expires' => $expires,
                'refreshToken' => 'newRefreshToken',
                'idToken' => ''
            ])
            ->willReturn($response);

        $context = $this->createStub(HandlerContext::class);
        $context->method('getKeycloakProvider')->willReturn($provider);

        $sut = new AuthenticationRefreshTokenHandler();

        $this->assertSame($response, $sut->handle($request, $responseFactory, $context));
    }

    public function testItReturnsBadRequestIfTokenIsInvalid(): void
    {
        $response = new \stdClass();

        $request = $this->createStub(Request::class);
        $request->method('getPostValue')->willReturn('refreshToken');
        $provider = $this->createMock(KeycloakProvider::class);
        $provider->expects($this->once())
            ->method('getAccessToken')
            ->willThrowException(new \Exception('Invalid token'));

        $responseFactory = $this->createMock(ResponseFactory::class);
        $responseFactory->expects($this->once())
            ->method('buildBadRequest')
            ->with('Invalid token')
            ->willReturn($response);

        $context = $this->createStub(HandlerContext::class);
        $context->method('getKeycloakProvider')->willReturn($provider);

        $sut = new AuthenticationRefreshTokenHandler();

        $this->assertSame($response, $sut->handle($request, $responseFactory, $context));
    }

    public function testItReturnsUnauthorizedIfTokenIsExpired(): void
    {
        $response = new \stdClass();

        $request = $this->createStub(Request::class);
        $request->method('getPostValue')->willReturn('refreshToken');
        $provider = $this->createMock(KeycloakProvider::class);
        $provider->expects($this->once())
            ->method('getAccessToken')
            ->willThrowException(new \Exception('Session not active'));

        $responseFactory = $this->createMock(ResponseFactory::class);
        $responseFactory->expects($this->never())->method('buildBadRequest');
        $responseFactory->expects($this->once())
            ->method('buildUnauthorized')
            ->with('Session not active')
            ->willReturn($response);

        $context = $this->createStub(HandlerContext::class);
        $context->method('getKeycloakProvider')->willReturn($provider);

        $sut = new AuthenticationRefreshTokenHandler();

        $this->assertSame($response, $sut->handle($request, $responseFactory, $context));
    }
}
