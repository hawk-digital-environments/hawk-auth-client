<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Keycloak\Query;


use GuzzleHttp\ClientInterface;
use Hawk\AuthClient\Cache\CacheAdapterInterface;
use Hawk\AuthClient\Exception\ChunkedQueryInfiniteLoopException;
use Psr\Http\Message\ResponseInterface;

/**
 * @internal This class is not part of the public API and may change at any time.
 */
abstract class AbstractChunkedQuery
{
    public function execute(ClientInterface $client, ?CacheAdapterInterface $cache = null): iterable
    {
        $first = 0;
        $chunkSize = $this->getChunkSize();
        $requestSize = $chunkSize + 1;

        $valueGenerator = function (int $first, int $requestSize) use ($client, $chunkSize): array {
            $response = $this->doRequest($client, $first, $requestSize);
            $data = $this->responseToData($response);
            return [
                count($data),
                array_slice($data, 0, $chunkSize)
            ];
        };

        if ($cache !== null) {
            $cacheKey = $this->getCacheKey();
            $valueGenerator = static fn(int $first, int $requestSize) => $cache->remember(
                'chunkedQuery.' . hash('sha256', $cacheKey . '-' . $first . '-' . $requestSize),
                valueGenerator: fn() => $valueGenerator($first, $requestSize)
            );
        }

        $maxIterations = $this->getMaxIterations();
        $iteration = 0;
        while (true) {
            [$dataCount, $data] = $valueGenerator($first, $requestSize);
            foreach ($data as $dataItem) {
                yield $this->dataToItem($dataItem);
            }

            if ($dataCount === 0 || $dataCount < $requestSize) {
                break;
            }

            $first += $chunkSize;

            if (++$iteration >= $maxIterations) {
                throw new ChunkedQueryInfiniteLoopException($maxIterations);
            }
        }
    }

    /**
     * Returns the number of items to request in each chunk. The actual number of items returned may be less than this
     * @return int
     */
    protected function getChunkSize(): int
    {
        return 100;
    }

    /**
     * The maximum number of requests to perform. This is a safety measure to prevent infinite loops.
     * 50000 * 100 = 5,000,000 items which should be enough for most cases.
     * If you need more, you can override this method.
     * @return int
     */
    protected function getMaxIterations(): int
    {
        return 50000;
    }

    /**
     * Extracts the payload from the response. The result should be an array of items.
     * @param ResponseInterface $response
     * @return array
     */
    protected function responseToData(ResponseInterface $response): array
    {
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * If a {@see CacheAdapterInterface} is provided, this method will be called to generate a base cache key to use for the
     * chunks of data. The key should be unique to the query and the parameters used.
     * @return string
     */
    protected function getCacheKey(): string
    {
        return get_class($this);
    }

    /**
     * The actual workhorse of the query. This method should perform the request to the API and return the response.
     * Depending on the number of items requested, this method may be called multiple times.
     *
     * @param ClientInterface $client
     * @param int $first
     * @param int $max
     * @return ResponseInterface
     */
    abstract protected function doRequest(ClientInterface $client, int $first, int $max): ResponseInterface;

    /**
     * Maps the raw data received by the request to the item that should be yielded.
     * @param mixed $dataItem
     * @return mixed
     */
    abstract protected function dataToItem(mixed $dataItem): mixed;
}
