<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Util;


use Hawk\AuthClient\Util\ErrorResponseMessage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

#[CoversClass(ErrorResponseMessage::class)]
class ErrorResponseMessageTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new ErrorResponseMessage($this->createStub(ResponseInterface::class));
        $this->assertInstanceOf(ErrorResponseMessage::class, $sut);
    }

    public function tstItSerializesMessageWithoutJsonBody(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(404);
        $response->method('getReasonPhrase')->willReturn('Not Found');

        $sut = new ErrorResponseMessage($response);
        $this->assertEquals('HTTP 404 Not Found', $sut->__toString());
    }

    public static function provideTestItSerializesMessageWithJsonBodyData(): iterable
    {
        yield 'error only' => [
            [
                'error' => 'invalid_request'
            ],
            'invalid_request'
        ];

        yield 'error and description' => [
            [
                'error' => 'invalid_request',
                'error_description' => 'The request is missing a required parameter'
            ],
            'invalid_request: The request is missing a required parameter'
        ];
    }

    #[DataProvider('provideTestItSerializesMessageWithJsonBodyData')]
    public function testItSerializesMessageWithJsonBody(array $body, string $expected): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(400);
        $response->method('getReasonPhrase')->willReturn('Bad Request');
        $bodyStream = $this->createStub(StreamInterface::class);
        $bodyStream->method('getContents')->willReturn(json_encode($body));
        $response->method('getBody')->willReturn($bodyStream);

        $sut = new ErrorResponseMessage($response);
        $this->assertEquals($expected, $sut->__toString());
    }

}
