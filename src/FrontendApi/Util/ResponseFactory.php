<?php
declare(strict_types=1);


namespace Hawk\AuthClient\FrontendApi\Util;


use DateTimeInterface;
use Hawk\AuthClient\Keycloak\CacheBusterStorage;

/**
 * @internal This class is not part of the public API and may change at any time.
 */
class ResponseFactory
{
    public static bool $unitTesting = false;

    /**
     * @var callable(array, array, int): mixed|null
     */
    protected mixed $concreteFactory;
    protected CacheBusterStorage $cacheBusterStorage;

    public function __construct(CacheBusterStorage $cacheBusterStorage)
    {
        $this->cacheBusterStorage = $cacheBusterStorage;
    }

    /**
     * @param callable(array, array, int): mixed $factory
     * @return void
     */
    public function setConcreteFactory(callable $factory): void
    {
        $this->concreteFactory = $factory;
    }

    public function buildUnauthorized(string|null $message = null): mixed
    {
        return $this->buildResponse(['error' => $message ?? 'Unauthorized'], 401);
    }

    public function buildBadRequest(string|null $message = null): mixed
    {
        return $this->buildResponse(['error' => $message ?? 'Bad Request'], 400);
    }

    public function buildNotModified(): mixed
    {
        return $this->buildCacheableResponse([], 304);
    }

    public function buildNotFound(string|null $message = null): mixed
    {
        return $this->buildResponse(['error' => $message ?? 'Not Found'], 404);
    }

    public function buildResponse(array $data, int|null $statusCode = null, array|null $headers = null): mixed
    {
        $headers = array_filter(
            array_merge(
                [
                    'Content-Type' => 'application/json',
                    'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                    'Pragma' => 'no-cache',
                    'Expires' => '0'
                ],
                $headers ?? []
            ),
            static fn($value) => $value !== null
        );

        return ($this->getConcreteResponseFactory())($data, $headers, $statusCode ?? 200);
    }

    public function buildCacheableResponse(array $data, int|null $statusCode = null, array|null $headers = null): mixed
    {
        $cacheBuster = $this->cacheBusterStorage->getCacheBuster();
        $cacheBusterDate = new \DateTimeImmutable('@' . (int)(((int)(string)$cacheBuster) / 1000));

        $headers = array_merge(
            [
                'Cache-Control' => 'public, max-age=10',
                'Pragma' => 'public',
                'Last-Modified' => $cacheBusterDate->format(DateTimeInterface::RFC1123),
                'ETag' => md5((string)$cacheBuster),
                'Expires' => null
            ],
            $headers ?? []
        );

        return $this->buildResponse($data, $statusCode, $headers);
    }

    /**
     * @return callable(array, array, int): mixed
     */
    protected function getConcreteResponseFactory(): callable
    {
        return $this->concreteFactory ?? static function (array $data, array $headers, int $statusCode) {
            // To avoid tainting our unit tests with side effects, we return the data instead of echoing it.
            if (static::$unitTesting) {
                return ['factory' => 'default', 'data' => $data, 'headers' => $headers, 'statusCode' => $statusCode];
            }

            // @codeCoverageIgnoreStart
            http_response_code($statusCode);
            foreach ($headers as $header => $value) {
                header($header . ': ' . $value);
            }
            echo json_encode($data);

            exit();
            // @codeCoverageIgnoreEnd
        };
    }
}
