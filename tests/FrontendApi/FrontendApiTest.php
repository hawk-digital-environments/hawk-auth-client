<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\FrontendApi;


use Hawk\AuthClient\FrontendApi\FrontendApi;
use Hawk\AuthClient\FrontendApi\Handler\AuthenticationExchangeCodeForTokenHandler;
use Hawk\AuthClient\FrontendApi\Handler\AuthenticationLoginUrlHandler;
use Hawk\AuthClient\FrontendApi\Handler\AuthenticationLogoutUrlHandler;
use Hawk\AuthClient\FrontendApi\Handler\AuthenticationRefreshTokenHandler;
use Hawk\AuthClient\FrontendApi\Handler\PermissionsHandler;
use Hawk\AuthClient\FrontendApi\Handler\UserInfoHandler;
use Hawk\AuthClient\FrontendApi\Handler\UserProfileHandler;
use Hawk\AuthClient\FrontendApi\Util\HandlerContext;
use Hawk\AuthClient\FrontendApi\Util\HandlerStack;
use Hawk\AuthClient\FrontendApi\Util\ResponseFactory;
use Hawk\AuthClient\Request\RequestAdapterInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FrontendApi::class)]
class FrontendApiTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new FrontendApi(
            $this->createStub(HandlerContext::class),
            $this->createStub(RequestAdapterInterface::class),
            $this->createStub(ResponseFactory::class),
            $this->createStub(HandlerStack::class)
        );
        $this->assertInstanceOf(FrontendApi::class, $sut);
    }

    public function testItSetsDefaultHandlers(): void
    {
        $stack = $this->createMock(HandlerStack::class);
        $stack->expects($this->exactly(4))->method('addHandler')
            ->with($this->logicalOr(
                $this->isInstanceOf(AuthenticationLoginUrlHandler::class),
                $this->isInstanceOf(AuthenticationExchangeCodeForTokenHandler::class),
                $this->isInstanceOf(AuthenticationRefreshTokenHandler::class),
                $this->isInstanceOf(AuthenticationLogoutUrlHandler::class)
            ));

        new FrontendApi(
            $this->createStub(HandlerContext::class),
            $this->createStub(RequestAdapterInterface::class),
            $this->createStub(ResponseFactory::class),
            $stack
        );
    }

    public function testItCanSetTheConcreteResponseFactory(): void
    {
        $concreteFactory = function () {
            $this->fail('Should not be called');
        };

        $responseFactory = $this->createMock(ResponseFactory::class);
        $responseFactory->expects($this->once())->method('setConcreteFactory')->with($concreteFactory);

        $sut = new FrontendApi(
            $this->createStub(HandlerContext::class),
            $this->createStub(RequestAdapterInterface::class),
            $responseFactory,
            $this->createStub(HandlerStack::class)
        );

        $sut->setResponseFactory($concreteFactory);
    }

    public function testItSetsFallthroughAllowed(): void
    {
        $stack = $this->createMock(HandlerStack::class);
        $stack->expects($this->once())->method('setFallthroughAllowed')->with(true);

        $sut = new FrontendApi(
            $this->createStub(HandlerContext::class),
            $this->createStub(RequestAdapterInterface::class),
            $this->createStub(ResponseFactory::class),
            $stack
        );

        $sut->setFallthroughAllowed();
    }

    public function testItCanEnableUserInfo(): void
    {
        $stack = $this->createMock(HandlerStack::class);
        $invokation = $this->exactly(5);
        $stack->expects($invokation)->method('addHandler')->with($this->callback(
            function ($arg) use ($invokation) {
                if ($invokation->numberOfInvocations() === 5) {
                    return $arg instanceof UserInfoHandler;
                }

                return true;
            }
        ));

        $sut = new FrontendApi(
            $this->createStub(HandlerContext::class),
            $this->createStub(RequestAdapterInterface::class),
            $this->createStub(ResponseFactory::class),
            $stack
        );

        $sut->enableUserInfo();
    }

    public function testItCanEnableUserProfile(): void
    {
        $stack = $this->createMock(HandlerStack::class);
        $invokation = $this->exactly(5);
        $stack->expects($invokation)->method('addHandler')->with($this->callback(
            function ($arg) use ($invokation) {
                if ($invokation->numberOfInvocations() === 5) {
                    return $arg instanceof UserProfileHandler;
                }

                return true;
            }
        ));

        $sut = new FrontendApi(
            $this->createStub(HandlerContext::class),
            $this->createStub(RequestAdapterInterface::class),
            $this->createStub(ResponseFactory::class),
            $stack
        );

        $sut->enableUserProfile();
    }

    public function testItCanEnablePermissions(): void
    {
        $stack = $this->createMock(HandlerStack::class);
        $invokation = $this->exactly(6);
        $stack->expects($invokation)->method('addHandler')->with($this->callback(
            function ($arg) use ($invokation) {
                if ($invokation->numberOfInvocations() === 5) {
                    return $arg instanceof UserInfoHandler;
                }
                if ($invokation->numberOfInvocations() === 6) {
                    return $arg instanceof PermissionsHandler;
                }

                return true;
            }
        ));

        $sut = new FrontendApi(
            $this->createStub(HandlerContext::class),
            $this->createStub(RequestAdapterInterface::class),
            $this->createStub(ResponseFactory::class),
            $stack
        );

        $sut->enablePermissions();
    }

    public function testItCanHandleRequest(): void
    {
        $response = new \stdClass();

        $context = $this->createStub(HandlerContext::class);
        $request = $this->createStub(RequestAdapterInterface::class);
        $responseFactory = $this->createMock(ResponseFactory::class);
        $stack = $this->createMock(HandlerStack::class);
        $stack->expects($this->once())->method('handle')
            ->with($request, $responseFactory, $context)
            ->willReturn($response);

        $_response = (new FrontendApi($context, $request, $responseFactory, $stack))
            ->handle();

        $this->assertSame($response, $_response);
    }
}
