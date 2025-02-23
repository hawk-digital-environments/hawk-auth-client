<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\FrontendApi\Util;


use Hawk\AuthClient\FrontendApi\Handler\FrontendApiHandlerInterface;
use Hawk\AuthClient\FrontendApi\Handler\PermissionsHandler;
use Hawk\AuthClient\FrontendApi\Handler\UserInfoHandler;
use Hawk\AuthClient\FrontendApi\Handler\UserProfileHandler;
use Hawk\AuthClient\FrontendApi\Util\HandlerContext;
use Hawk\AuthClient\FrontendApi\Util\HandlerStack;
use Hawk\AuthClient\FrontendApi\Util\Request;
use Hawk\AuthClient\FrontendApi\Util\ResponseFactory;
use Hawk\AuthClient\Keycloak\Value\CacheBuster;
use Hawk\AuthClient\Request\RequestAdapterInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HandlerStack::class)]
class HandlerStackTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new HandlerStack();
        $this->assertInstanceOf(HandlerStack::class, $sut);
    }

    public function testItCanAddHandlers(): void
    {
        $sut = new class extends HandlerStack {
            public function getHandlers(): array
            {
                return array_values($this->handlers);
            }
        };

        $handler1 = new UserInfoHandler();
        $handler2 = new PermissionsHandler();
        $handler3 = new UserProfileHandler();
        $handler4 = new UserProfileHandler();

        $sut->addHandler($handler1);
        $sut->addHandler($handler2);
        $sut->addHandler($handler3);
        $sut->addHandler($handler4);

        // Handler three gets overwritten by handler four, because they have the same class name
        $this->assertEquals([$handler1, $handler2, $handler4], $sut->getHandlers());
    }

    public function testItCanSetFallthroughAllowed(): void
    {
        $sut = new class extends HandlerStack {
            public function isFallthroughAllowed(): bool
            {
                return $this->fallthroughAllowed;
            }
        };

        $this->assertFalse($sut->isFallthroughAllowed());
        $sut->setFallthroughAllowed(true);
        $this->assertTrue($sut->isFallthroughAllowed());
    }

    public function testItHandlesRequestWithNoRouteAndWithoutFallthroughAllowed(): void
    {
        $response = new \stdClass();
        $sut = new HandlerStack();
        $request = $this->createStub(RequestAdapterInterface::class);
        $request->method('getQueryValue')->willReturn(null);
        $responseFactory = $this->createMock(ResponseFactory::class);
        $responseFactory->method('buildBadRequest')
            ->with($this->stringStartsWith('Your request is missing the required'))
            ->willReturn($response);
        $result = $sut->handle($request, $responseFactory, $this->createStub(HandlerContext::class));
        $this->assertSame($response, $result);
    }

    public function testItHandlesRequestWithNoRouteButWithFallthroughAllowed(): void
    {
        $sut = new HandlerStack();
        $sut->setFallthroughAllowed(true);
        $request = $this->createStub(RequestAdapterInterface::class);
        $request->method('getQueryValue')->willReturn(null);
        $responseFactory = $this->createMock(ResponseFactory::class);
        $responseFactory->expects($this->never())->method('buildBadRequest');
        $result = $sut->handle($request, $responseFactory, $this->createStub(HandlerContext::class));
        $this->assertNull($result);
    }


    public function testItHandlesCachedRequestWithETag(): void
    {
        $response = new \stdClass();
        $now = new \DateTimeImmutable();
        $cacheBuster = new CacheBuster((string)($now->getTimestamp() * 1000));
        $etag = md5((string)$cacheBuster);
        $context = $this->createStub(HandlerContext::class);
        $context->method('getCacheBuster')->willReturn($cacheBuster);
        $request = $this->createStub(RequestAdapterInterface::class);
        $request->method('getQueryValue')->willReturn('foo');
        $request->method('getHeaderValue')->with('If-None-Match')->willReturn($etag);
        $responseFactory = $this->createMock(ResponseFactory::class);
        $responseFactory->expects($this->once())->method('buildNotModified')->willReturn($response);
        $result = (new HandlerStack())->handle($request, $responseFactory, $context);
        $this->assertSame($response, $result);
    }

    public function testItIteratesHandlersAndReturnsMatchingResponse(): void
    {
        $response = new \stdClass();
        $responseFactory = $this->createMock(ResponseFactory::class);
        $responseFactory->expects($this->never())->method('buildNotFound');
        $responseFactory->expects($this->never())->method('buildNotModified');
        $responseFactory->expects($this->never())->method('buildBadRequest');

        $context = $this->createStub(HandlerContext::class);

        $handler1 = $this->getMockBuilder(FrontendApiHandlerInterface::class)
            ->setMockClassName('FrontendApiHandlerMock1')
            ->disableOriginalConstructor()
            ->getMock();
        $handler1->expects($this->once())->method('canHandle')->with($this->isInstanceOf(Request::class))->willReturn(false);
        $handler1->expects($this->never())->method('handle');

        $handler2 = $this->createMock(FrontendApiHandlerInterface::class);
        $handler2->expects($this->once())->method('canHandle')->with($this->isInstanceOf(Request::class))->willReturn(true);
        $handler2->expects($this->once())->method('handle')->with(
            $this->isInstanceOf(Request::class),
            $responseFactory,
            $context
        )->willReturn($response);

        $sut = new HandlerStack();

        $sut->addHandler($handler1);
        $sut->addHandler($handler2);

        $request = $this->createStub(RequestAdapterInterface::class);
        $request->method('getQueryValue')->willReturn('foo');

        $result = $sut->handle($request, $responseFactory, $context);
        $this->assertSame($response, $result);
    }

    public function testItReturns404IfRouteWasNotFound(): void
    {
        $response = new \stdClass();
        $responseFactory = $this->createMock(ResponseFactory::class);
        $responseFactory->expects($this->once())->method('buildNotFound')->willReturn($response);
        $context = $this->createStub(HandlerContext::class);

        $handler = $this->createMock(FrontendApiHandlerInterface::class);
        $handler->expects($this->once())->method('canHandle')->with($this->isInstanceOf(Request::class))->willReturn(false);
        $handler->expects($this->never())->method('handle');

        $sut = new HandlerStack();
        $sut->addHandler($handler);

        $request = $this->createStub(RequestAdapterInterface::class);
        $request->method('getQueryValue')->willReturn('foo');

        $result = $sut->handle($request, $responseFactory, $context);
        $this->assertSame($response, $result);
    }

}
