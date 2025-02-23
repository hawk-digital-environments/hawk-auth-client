<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\FrontendApi\Handler;


use Hawk\AuthClient\Auth\KeycloakProvider;
use Hawk\AuthClient\FrontendApi\Handler\PermissionsHandler;
use Hawk\AuthClient\FrontendApi\Util\HandlerContext;
use Hawk\AuthClient\FrontendApi\Util\Request;
use Hawk\AuthClient\FrontendApi\Util\ResponseFactory;
use Hawk\AuthClient\Permissions\PermissionStorage;
use Hawk\AuthClient\Resources\Value\ResourceScopes;
use Hawk\AuthClient\Users\UserStorage;
use Hawk\AuthClient\Users\Value\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PermissionsHandler::class)]
class PermissionsHandlerTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new PermissionsHandler();
        $this->assertInstanceOf(PermissionsHandler::class, $sut);
    }

    public function testItDetectsIfItCanHandle(): void
    {
        $sut = new PermissionsHandler();
        $request = $this->createStub(Request::class);
        $request->method('getRoute')->willReturn(PermissionsHandler::ROUTE);
        $this->assertTrue($sut->canHandle($request));

        $request = $this->createStub(Request::class);
        $request->method('getRoute')->willReturn('permission');
        $this->assertFalse($sut->canHandle($request));
    }

    public function testItReturnsUnauthorizedWithoutBearerToken(): void
    {
        $sut = new PermissionsHandler();

        $request = $this->createStub(Request::class);
        $request->method('getBearerToken')->willReturn('');

        $responseFactory = $this->createMock(ResponseFactory::class);
        $responseFactory->expects($this->once())
            ->method('buildUnauthorized')
            ->with()
            ->willReturn(['status' => 'unauthorized']);

        $context = $this->createStub(HandlerContext::class);

        $result = $sut->handle($request, $responseFactory, $context);
        $this->assertEquals(['status' => 'unauthorized'], $result);
    }

    public function testItReturnsBadRequestWithInvalidResourceId(): void
    {
        $sut = new PermissionsHandler();

        $request = $this->createStub(Request::class);
        $request->method('getBearerToken')->willReturn('valid-token');
        $request->method('getQueryValue')->with('resource')->willReturn('');

        $responseFactory = $this->createMock(ResponseFactory::class);
        $responseFactory->expects($this->once())
            ->method('buildBadRequest')
            ->with('Invalid resource ID')
            ->willReturn(['status' => 'bad_request']);

        $context = $this->createStub(HandlerContext::class);

        $result = $sut->handle($request, $responseFactory, $context);
        $this->assertEquals(['status' => 'bad_request'], $result);
    }

    public function testItReturnsUnauthorizedWhenTokenIsInvalid(): void
    {
        $sut = new PermissionsHandler();

        $request = $this->createStub(Request::class);
        $request->method('getBearerToken')->willReturn('invalid-token');
        $request->method('getQueryValue')->with('resource')->willReturn('resource-123');

        $responseFactory = $this->createStub(ResponseFactory::class);
        $responseFactory->method('buildUnauthorized')
            ->willReturn(['status' => 'unauthorized', 'message' => 'Token validation failed']);

        $keycloakProvider = $this->createStub(KeycloakProvider::class);
        $keycloakProvider->method('getResourceOwner')
            ->willThrowException(new \Exception('Token validation failed'));
        $userStorage = $this->createStub(UserStorage::class);
        $userStorage->method('getOneByToken')->willReturnCallback(fn($_, callable $fallback) => $fallback());

        $context = $this->createStub(HandlerContext::class);
        $context->method('getKeycloakProvider')->willReturn($keycloakProvider);
        $context->method('getUserStorage')->willReturn($userStorage);

        $result = $sut->handle($request, $responseFactory, $context);
        $this->assertEquals(['status' => 'unauthorized', 'message' => 'Token validation failed'], $result);
    }

    public function testItReturnsUnauthorizedWhenUserNotFound(): void
    {
        $sut = new PermissionsHandler();

        $request = $this->createStub(Request::class);
        $request->method('getBearerToken')->willReturn('valid-token');
        $request->method('getQueryValue')->with('resource')->willReturn('resource-123');

        $responseFactory = $this->createMock(ResponseFactory::class);
        $responseFactory->expects($this->once())
            ->method('buildUnauthorized')
            ->with()
            ->willReturn(['status' => 'unauthorized']);

        $userStorage = $this->createStub(UserStorage::class);
        $userStorage->method('getOneByToken')->willReturn(null);

        $context = $this->createStub(HandlerContext::class);
        $context->method('getUserStorage')->willReturn($userStorage);

        $result = $sut->handle($request, $responseFactory, $context);
        $this->assertEquals(['status' => 'unauthorized'], $result);
    }

    public function testItReturnsResourceScopesForValidUser(): void
    {
        $sut = new PermissionsHandler();

        $request = $this->createStub(Request::class);
        $request->method('getBearerToken')->willReturn('valid-token');
        $request->method('getQueryValue')->with('resource')->willReturn('resource-123');

        $scopes = new ResourceScopes('read', 'write');

        $responseFactory = $this->createMock(ResponseFactory::class);
        $responseFactory->expects($this->once())
            ->method('buildResponse')
            ->with(['scopes' => $scopes])
            ->willReturn(['status' => 'success', 'scopes' => ['read', 'write']]);

        $user = $this->createStub(User::class);

        $userStorage = $this->createStub(UserStorage::class);
        $userStorage->method('getOneByToken')->willReturn($user);

        $permissionStorage = $this->createStub(PermissionStorage::class);
        $permissionStorage->method('getGrantedResourceScopes')
            ->with('resource-123', $user)
            ->willReturn($scopes);

        $context = $this->createStub(HandlerContext::class);
        $context->method('getUserStorage')->willReturn($userStorage);
        $context->method('getPermissionStorage')->willReturn($permissionStorage);

        $result = $sut->handle($request, $responseFactory, $context);
        $this->assertEquals(['status' => 'success', 'scopes' => ['read', 'write']], $result);
    }
}
