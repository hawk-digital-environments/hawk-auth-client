<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\FrontendApi\Util;


use DateTimeInterface;
use Hawk\AuthClient\FrontendApi\Util\ResponseFactory;
use Hawk\AuthClient\Keycloak\CacheBusterStorage;
use Hawk\AuthClient\Keycloak\Value\CacheBuster;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ResponseFactory::class)]
class ResponseFactoryTest extends TestCase
{
    protected function tearDown(): void
    {
        ResponseFactory::$unitTesting = false;
    }

    public function testItConstructs(): void
    {
        $sut = new ResponseFactory($this->createStub(CacheBusterStorage::class));
        $this->assertInstanceOf(ResponseFactory::class, $sut);
    }

    public function testItCanBuildUnauthorized(): void
    {
        $sut = new ResponseFactory($this->createStub(CacheBusterStorage::class));
        $sut->setConcreteFactory(function (array $data, array $headers, int $statusCode) {
            return ['data' => $data, 'headers' => $headers, 'statusCode' => $statusCode];
        });
        $response = $sut->buildUnauthorized();
        $this->assertEquals([
            'data' => ['error' => 'Unauthorized'],
            'headers' => [
                'Content-Type' => 'application/json',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ],
            'statusCode' => 401
        ], $response);

        $response = $sut->buildUnauthorized('Custom message');
        $this->assertEquals('Custom message', $response['data']['error']);
    }

    public function testItCanBuildBadRequest(): void
    {
        $sut = new ResponseFactory($this->createStub(CacheBusterStorage::class));
        $sut->setConcreteFactory(function (array $data, array $headers, int $statusCode) {
            return ['data' => $data, 'headers' => $headers, 'statusCode' => $statusCode];
        });
        $response = $sut->buildBadRequest();
        $this->assertEquals([
            'data' => ['error' => 'Bad Request'],
            'headers' => [
                'Content-Type' => 'application/json',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ],
            'statusCode' => 400
        ], $response);

        $response = $sut->buildBadRequest('Custom message');
        $this->assertEquals('Custom message', $response['data']['error']);
    }

    public function testItCanBuildNotModified(): void
    {
        $now = new \DateTimeImmutable();
        $cacheBuster = new CacheBuster((string)($now->getTimestamp() * 1000));
        $cacheBusterStorage = $this->createStub(CacheBusterStorage::class);
        $cacheBusterStorage->method('getCacheBuster')->willReturn($cacheBuster);
        $sut = new ResponseFactory($cacheBusterStorage);
        $sut->setConcreteFactory(function (array $data, array $headers, int $statusCode) {
            return ['data' => $data, 'headers' => $headers, 'statusCode' => $statusCode];
        });
        $response = $sut->buildNotModified();
        $this->assertEquals([
            'data' => [],
            'headers' => [
                'Content-Type' => 'application/json',
                'Cache-Control' => 'public, max-age=10',
                'Last-Modified' => $now->format(DateTimeInterface::RFC1123),
                'ETag' => md5((string)$cacheBuster),
                'Pragma' => 'public'
            ],
            'statusCode' => 304
        ], $response);
    }

    public function testItCanBuildNotFound(): void
    {
        $sut = new ResponseFactory($this->createStub(CacheBusterStorage::class));
        $sut->setConcreteFactory(function (array $data, array $headers, int $statusCode) {
            return ['data' => $data, 'headers' => $headers, 'statusCode' => $statusCode];
        });
        $response = $sut->buildNotFound();
        $this->assertEquals([
            'data' => ['error' => 'Not Found'],
            'headers' => [
                'Content-Type' => 'application/json',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ],
            'statusCode' => 404
        ], $response);

        $response = $sut->buildNotFound('Custom message');
        $this->assertEquals('Custom message', $response['data']['error']);
    }

    public function testItCanBuildResponse(): void
    {
        $sut = new ResponseFactory($this->createStub(CacheBusterStorage::class));
        $sut->setConcreteFactory(function (array $data, array $headers, int $statusCode) {
            return ['data' => $data, 'headers' => $headers, 'statusCode' => $statusCode];
        });
        $response = $sut->buildResponse(['key' => 'value']);
        $this->assertEquals([
            'data' => ['key' => 'value'],
            'headers' => [
                'Content-Type' => 'application/json',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ],
            'statusCode' => 200
        ], $response);

        $response = $sut->buildResponse(['key' => 'value'], 201, ['Custom' => 'header']);
        $this->assertEquals([
            'data' => ['key' => 'value'],
            'headers' => [
                'Content-Type' => 'application/json',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
                'Expires' => '0',
                'Custom' => 'header'
            ],
            'statusCode' => 201
        ], $response);
    }

    public function testItCanBuildCacheableResponse(): void
    {
        $now = new \DateTimeImmutable();
        $cacheBuster = new CacheBuster((string)($now->getTimestamp() * 1000));
        $cacheBusterStorage = $this->createStub(CacheBusterStorage::class);
        $cacheBusterStorage->method('getCacheBuster')->willReturn($cacheBuster);
        $sut = new ResponseFactory($cacheBusterStorage);
        $sut->setConcreteFactory(function (array $data, array $headers, int $statusCode) {
            return ['data' => $data, 'headers' => $headers, 'statusCode' => $statusCode];
        });
        $response = $sut->buildCacheableResponse(['key' => 'value']);
        $this->assertEquals([
            'data' => ['key' => 'value'],
            'headers' => [
                'Content-Type' => 'application/json',
                'Cache-Control' => 'public, max-age=10',
                'Last-Modified' => $now->format(DateTimeInterface::RFC1123),
                'ETag' => md5((string)$cacheBuster),
                'Pragma' => 'public'
            ],
            'statusCode' => 200
        ], $response);

        $response = $sut->buildCacheableResponse(['key' => 'value'], 201, ['Custom' => 'header']);
        $this->assertEquals([
            'data' => ['key' => 'value'],
            'headers' => [
                'Content-Type' => 'application/json',
                'Cache-Control' => 'public, max-age=10',
                'Pragma' => 'public',
                'Last-Modified' => $now->format(DateTimeInterface::RFC1123),
                'ETag' => md5((string)$cacheBuster),
                'Custom' => 'header'
            ],
            'statusCode' => 201
        ], $response);
    }

    public function testItUsesTheDefaultResponseFactoryWhenNotGivenOneExplicitly(): void
    {
        ResponseFactory::$unitTesting = true;
        $response = (new ResponseFactory($this->createStub(CacheBusterStorage::class)))->buildUnauthorized();
        $this->assertEquals([
            'factory' => 'default',
            'data' => ['error' => 'Unauthorized'],
            'headers' => [
                'Content-Type' => 'application/json',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ],
            'statusCode' => 401
        ], $response);
    }
}
