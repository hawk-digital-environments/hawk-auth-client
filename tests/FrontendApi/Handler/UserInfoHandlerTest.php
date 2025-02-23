<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\FrontendApi\Handler;


use Hawk\AuthClient\FrontendApi\Handler\UserInfoHandler;
use Hawk\AuthClient\FrontendApi\Util\CurrentUserFinder;
use Hawk\AuthClient\FrontendApi\Util\HandlerContext;
use Hawk\AuthClient\FrontendApi\Util\Request;
use Hawk\AuthClient\FrontendApi\Util\ResponseFactory;
use Hawk\AuthClient\Users\Value\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UserInfoHandler::class)]
class UserInfoHandlerTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new UserInfoHandler();
        $this->assertInstanceOf(UserInfoHandler::class, $sut);
    }

    public function testItDetectsIfItCanHandle(): void
    {
        $sut = new UserInfoHandler();
        $request = $this->createStub(Request::class);
        $request->method('getRoute')->willReturn(UserInfoHandler::ROUTE);
        $this->assertTrue($sut->canHandle($request));

        $request = $this->createStub(Request::class);
        $request->method('getRoute')->willReturn('user-infos');
        $this->assertFalse($sut->canHandle($request));
    }

    public function testItReturnsResponseIfUserCouldNotBeFound(): void
    {
        $request = $this->createStub(Request::class);
        $responseFactory = $this->createMock(ResponseFactory::class);
        $responseFactory->expects($this->never())->method('buildCacheableResponse');
        $context = $this->createStub(HandlerContext::class);

        $response = new \stdClass();
        $userFinder = $this->createMock(CurrentUserFinder::class);
        $userFinder->expects($this->once())->method('findUser')->with($request, $responseFactory)->willReturn($response);
        $context->method('getCurrentUserFinder')->willReturn($userFinder);

        $sut = new UserInfoHandler();
        $this->assertSame($response, $sut->handle($request, $responseFactory, $context));
    }

    public function testItBuildUserInfo(): void
    {
        $expected = ['user' => 'info'];
        $user = $this->createStub(User::class);
        $user->method('toArray')->willReturn($expected);

        $request = $this->createStub(Request::class);
        $response = new \stdClass();
        $responseFactory = $this->createMock(ResponseFactory::class);
        $responseFactory->expects($this->once())->method('buildCacheableResponse')->with($expected)->willReturn($response);

        $context = $this->createStub(HandlerContext::class);
        $userFinder = $this->createMock(CurrentUserFinder::class);
        $userFinder->expects($this->once())->method('findUser')->with($request, $responseFactory)->willReturn($user);
        $context->method('getCurrentUserFinder')->willReturn($userFinder);

        $sut = new UserInfoHandler();
        $this->assertSame($response, $sut->handle($request, $responseFactory, $context));
    }
}
