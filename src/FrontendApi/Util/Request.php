<?php
declare(strict_types=1);


namespace Hawk\AuthClient\FrontendApi\Util;


use Hawk\AuthClient\Request\RequestAdapterInterface;

/**
 * @internal This class is not part of the public API and may change at any time.
 */
class Request implements RequestAdapterInterface
{
    protected string $route;
    protected RequestAdapterInterface $rootRequest;

    public function __construct(
        string                  $route,
        RequestAdapterInterface $rootRequest
    )
    {
        $this->route = $route;
        $this->rootRequest = $rootRequest;
    }

    /**
     * Returns the "route" part of the query string.
     * This parameter normally determines which handler should be called.
     * @return string
     */
    public function getRoute(): string
    {
        return $this->route;
    }

    /**
     * Returns the bearer token from the Authorization header.
     * If the header is missing or the token is empty, null is returned.
     * @return string|null
     */
    public function getBearerToken(): string|null
    {
        $token = str_replace('Bearer ', '', (string)$this->rootRequest->getHeaderValue('Authorization'));
        return empty($token) ? null : $token;
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function getQueryValue(string $key): ?string
    {
        return $this->rootRequest->getQueryValue($key);
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function getPostValue(string $key): mixed
    {
        return $this->rootRequest->getPostValue($key);
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function getHeaderValue(string $key): ?string
    {
        return $this->rootRequest->getHeaderValue($key);
    }
}
