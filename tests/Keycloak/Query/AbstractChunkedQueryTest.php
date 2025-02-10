<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Keycloak\Query;


use GuzzleHttp\ClientInterface;
use Hawk\AuthClient\Cache\CacheAdapterInterface;
use Hawk\AuthClient\Exception\ChunkedQueryInfiniteLoopException;
use Hawk\AuthClient\Keycloak\Query\AbstractChunkedQuery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

#[CoversClass(AbstractChunkedQuery::class)]
#[CoversClass(ChunkedQueryInfiniteLoopException::class)]
class AbstractChunkedQueryTest extends TestCase
{
    public function testItDoesChunkedQueriesWithoutCache(): void
    {
        $responses = [
            '0-5' => $this->createStreamResponse('[1,2,3,4,5]'),
            '4-5' => $this->createStreamResponse('[5,6,7,8,9]'),
            '8-5' => $this->createStreamResponse('[9,10,11,12,13]'),
            '12-5' => $this->createStreamResponse('[13,14,15]'),
        ];

        $sut = new class extends AbstractChunkedQuery {
            public array $responses;
            public array $requests = [];
            public array $dataToItemCalls = [];

            /**
             * @inheritDoc
             */
            #[\Override] protected function doRequest(ClientInterface $client, int $first, int $max): ResponseInterface
            {
                $this->requests[] = [$first, $max];
                return $this->responses["$first-$max"];
            }

            #[\Override] protected function getChunkSize(): int
            {
                return 4;
            }

            #[\Override] protected function dataToItem(mixed $dataItem): mixed
            {
                $this->dataToItemCalls[] = $dataItem;
                return $dataItem;
            }
        };
        $sut->responses = $responses;

        $result = $sut->execute($this->createStub(ClientInterface::class));

        $this->assertEquals([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15], iterator_to_array($result));
        $this->assertEquals([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15], $sut->dataToItemCalls);
        $this->assertEquals([[0, 5], [4, 5], [8, 5], [12, 5]], $sut->requests);
    }

    public function testItDetectsInfiniteLoops(): void
    {

        $this->expectException(ChunkedQueryInfiniteLoopException::class);
        $sut = new class extends AbstractChunkedQuery {
            public ResponseInterface $response;

            #[\Override] protected function doRequest(ClientInterface $client, int $first, int $max): ResponseInterface
            {
                return $this->response;
            }

            #[\Override] protected function getChunkSize(): int
            {
                return 1;
            }

            #[\Override] protected function getMaxIterations(): int
            {
                return 10;
            }

            #[\Override] protected function dataToItem(mixed $dataItem): mixed
            {
                return $dataItem;
            }
        };
        $sut->response = $this->createStreamResponse('[1,2]');

        iterator_to_array($sut->execute($this->createStub(ClientInterface::class)));
    }

    public function testItCanCacheChunks(): void
    {
        $responses = [
            '8-5' => $this->createStreamResponse('[9,10,11,12,13]'),
            '12-5' => $this->createStreamResponse('[13,14,15]'),
        ];

        $cacheKey = static fn(int $first, int $max) => "chunkedQuery." . hash('sha256', 'foo' . '-' . $first . '-' . $max);
        $cached = [
            ($cacheKey(0, 5)) => [5, [1, 2, 3, 4]],
            ($cacheKey(4, 5)) => [5, [5, 6, 7, 8]],
        ];

        $cache = $this->createMock(CacheAdapterInterface::class);
        $cache->expects($this->exactly(4))
            ->method('remember')
            ->willReturnCallback(
                function (string $key, callable $valueGenerator) use ($cached) {
                    return $cached[$key] ?? $valueGenerator();
                }
            );

        $sut = new class extends AbstractChunkedQuery {
            public array $responses;
            public array $requests = [];
            public array $dataToItemCalls = [];

            #[\Override] protected function doRequest(ClientInterface $client, int $first, int $max): ResponseInterface
            {
                $this->requests[] = [$first, $max];
                return $this->responses["$first-$max"];
            }

            #[\Override] protected function getChunkSize(): int
            {
                return 4;
            }

            #[\Override] protected function dataToItem(mixed $dataItem): mixed
            {
                $this->dataToItemCalls[] = $dataItem;
                return $dataItem;
            }

            #[\Override] protected function getCacheKey(): string
            {
                return 'foo';
            }
        };
        $sut->responses = $responses;

        $result = $sut->execute($this->createStub(ClientInterface::class), $cache);

        $this->assertEquals([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15], iterator_to_array($result));
        $this->assertEquals([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15], $sut->dataToItemCalls);
        $this->assertEquals([[8, 5], [12, 5]], $sut->requests);
    }

    public function testItEndsIterationIfAnEmptyArrayIsReturned(): void
    {
        $responses = [
            '0-101' => $this->createStreamResponse('[]'),
        ];

        $sut = new class extends AbstractChunkedQuery {
            public array $responses;
            public array $requests = [];
            public array $dataToItemCalls = [];

            #[\Override] protected function doRequest(ClientInterface $client, int $first, int $max): ResponseInterface
            {
                $this->requests[] = [$first, $max];
                return $this->responses["$first-$max"];
            }

            #[\Override] protected function dataToItem(mixed $dataItem): mixed
            {
                $this->dataToItemCalls[] = $dataItem;
                return $dataItem;
            }
        };
        $sut->responses = $responses;

        $cache = $this->createStub(CacheAdapterInterface::class);
        $cache->method('remember')->willReturnCallback(fn($_, $valueGenerator) => $valueGenerator());

        $result = $sut->execute($this->createStub(ClientInterface::class), $cache);

        $this->assertEquals([], iterator_to_array($result));
        $this->assertEquals([], $sut->dataToItemCalls);
        $this->assertEquals([[0, 101]], $sut->requests);

    }

    protected function createStreamResponse(string $content): ResponseInterface
    {
        $response = $this->createStub(ResponseInterface::class);
        $stream = $this->createStub(StreamInterface::class);
        $stream->method('getContents')->willReturn($content);
        $response->method('getBody')->willReturn($stream);
        return $response;
    }
}
