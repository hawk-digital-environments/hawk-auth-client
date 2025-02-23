<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\FrontendApi\Util;


use Hawk\AuthClient\Auth\KeycloakProvider;
use Hawk\AuthClient\FrontendApi\Util\CurrentUserFinder;
use Hawk\AuthClient\FrontendApi\Util\Request;
use Hawk\AuthClient\FrontendApi\Util\ResponseFactory;
use Hawk\AuthClient\Users\UserStorage;
use Hawk\AuthClient\Users\Value\User;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CurrentUserFinder::class)]
class CurrentUserFinderTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new CurrentUserFinder(
            $this->createStub(UserStorage::class),
            $this->createStub(KeycloakProvider::class)
        );
        $this->assertInstanceOf(CurrentUserFinder::class, $sut);
    }

    public function testItWillReturnUnauthorizedIfNoToken(): void
    {
        $request = $this->createStub(Request::class);
        $request->method('getBearerToken')->willReturn(null);

        $response = new \stdClass();

        $responseFactory = $this->createMock(ResponseFactory::class);
        $responseFactory->expects($this->once())->method('buildUnauthorized')->willReturn($response);

        $sut = new CurrentUserFinder(
            $this->createStub(UserStorage::class),
            $this->createStub(KeycloakProvider::class)
        );

        $this->assertSame($response, $sut->findUser($request, $responseFactory));
    }

    public function testItWillReturnUnauthorizedIfNoUserForToken(): void
    {
        $request = $this->createStub(Request::class);
        $request->method('getBearerToken')->willReturn('token');

        $response = new \stdClass();

        $responseFactory = $this->createMock(ResponseFactory::class);
        $responseFactory->expects($this->once())->method('buildUnauthorized')->willReturn($response);

        $userStorage = $this->createMock(UserStorage::class);
        $userStorage->expects($this->once())->method('getOneByToken')->willReturn(null);

        $sut = new CurrentUserFinder(
            $userStorage,
            $this->createStub(KeycloakProvider::class)
        );

        $this->assertSame($response, $sut->findUser($request, $responseFactory));
    }

    public function testItWillReturnUnauthorizedIfUserLookupThrowsAnException(): void
    {
        $request = $this->createStub(Request::class);
        $request->method('getBearerToken')->willReturn('token');

        $response = new \stdClass();

        $responseFactory = $this->createMock(ResponseFactory::class);
        $responseFactory->expects($this->once())->method('buildUnauthorized')->willReturn($response);

        $userStorage = $this->createMock(UserStorage::class);
        $userStorage->expects($this->once())->method('getOneByToken')->willThrowException(new \Exception());

        $sut = new CurrentUserFinder(
            $userStorage,
            $this->createStub(KeycloakProvider::class)
        );

        $this->assertSame($response, $sut->findUser($request, $responseFactory));
    }

    public function testItWillLookUpAUserByToken(): void
    {
        $request = $this->createStub(Request::class);
        $request->method('getBearerToken')->willReturn('token');

        $response = new \stdClass();

        $responseFactory = $this->createMock(ResponseFactory::class);
        $responseFactory->expects($this->never())->method('buildUnauthorized')->willReturn($response);

        $user = $this->createStub(User::class);

        $userStorage = $this->createMock(UserStorage::class);
        $userStorage->expects($this->once())
            ->method('getOneByToken')
            ->with(
                $this->callback(function ($token) {
                    $this->assertInstanceOf(AccessToken::class, $token);
                    $this->assertEquals('token', $token->getToken());
                    return true;
                }),
                $this->isCallable()
            )
            ->willReturn($user);

        $keycloakProvider = $this->createMock(KeycloakProvider::class);
        $keycloakProvider->expects($this->never())->method('getResourceOwner');

        $sut = new CurrentUserFinder(
            $userStorage,
            $keycloakProvider
        );

        $this->assertSame($user, $sut->findUser($request, $responseFactory));
    }

    public function testItConfiguresUserLookupCallbackCorrectly(): void
    {
        $request = $this->createStub(Request::class);
        $request->method('getBearerToken')->willReturn('token');

        $response = new \stdClass();

        $responseFactory = $this->createMock(ResponseFactory::class);
        $responseFactory->expects($this->never())->method('buildUnauthorized')->willReturn($response);

        $user = $this->createStub(User::class);

        $userStorage = $this->createMock(UserStorage::class);
        $userStorage->expects($this->once())
            ->method('getOneByToken')
            ->with(
                $this->isInstanceOf(AccessToken::class),
                $this->callback(function ($callback) use ($user) {
                    $this->assertInstanceOf(\Closure::class, $callback);
                    $this->assertSame($user, $callback());
                    return true;
                })
            )
            ->willReturn($user);

        $keycloakProvider = $this->createMock(KeycloakProvider::class);
        $keycloakProvider->expects($this->once())
            ->method('getResourceOwner')
            ->with($this->isInstanceOf(AccessToken::class))
            ->willReturn($user);

        $sut = new CurrentUserFinder(
            $userStorage,
            $keycloakProvider
        );

        $this->assertSame($user, $sut->findUser($request, $responseFactory));
    }
}
