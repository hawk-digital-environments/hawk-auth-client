<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Keycloak;


use Hawk\AuthClient\Cache\CacheAdapterInterface;
use Hawk\AuthClient\Keycloak\Value\ApiToken;
use Psr\Clock\ClockInterface;

/**
 * @internal This class is not part of the public API and may change at any time.
 */
class ApiTokenStorage
{
    private const string CACHE_KEY = 'keycloak.client.api_token';
    private const int TTL_LEEWAY = 30;

    private CacheAdapterInterface $cache;
    private ClockInterface $clock;
    private ApiToken|null $token = null;
    private KeycloakApiClient $api;

    public function __construct(
        CacheAdapterInterface $cache,
        ClockInterface        $clock,
        KeycloakApiClient     $api
    )
    {
        $this->cache = $cache;
        $this->clock = $clock;
        $this->api = $api;
    }

    public function getToken(): ApiToken
    {
        $this->token ??= $this->cache->remember(
            self::CACHE_KEY,
            valueGenerator: fn() => $this->api->fetchApiToken(),
            valueToCache: fn(ApiToken $token) => $token->jsonSerialize(),
            cacheToValue: fn(array $data) => ApiToken::fromArray($data),
            ttl: fn(ApiToken $token) => $token->getExpiresAt()->getTimestamp() - $this->clock->now()->getTimestamp() - self::TTL_LEEWAY
        );

        if ($this->token->isExpired($this->clock->now())) {
            $this->token = null;
            $this->cache->delete(self::CACHE_KEY);
            // Recursive call to fetch a new token
            return $this->getToken();
        }

        return $this->token;
    }
}
