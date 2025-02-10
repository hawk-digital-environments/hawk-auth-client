<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Permissions;


use Hawk\AuthClient\Cache\CacheAdapterInterface;
use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Resources\ResourceStorage;
use Hawk\AuthClient\Resources\Value\Resource;
use Hawk\AuthClient\Resources\Value\ResourceScopes;
use Hawk\AuthClient\Users\Value\User;

class PermissionStorage
{
    protected CacheAdapterInterface $cache;
    protected KeycloakApiClient $api;
    protected ResourceStorage $resourceStorage;
    protected array $grantedScopes = [];

    public function __construct(
        CacheAdapterInterface $cache,
        KeycloakApiClient     $api,
        ResourceStorage       $resourceStorage,
    )
    {
        $this->cache = $cache;
        $this->api = $api;
        $this->resourceStorage = $resourceStorage;
    }

    public function getGrantedResourceScopes(string|\Stringable|Resource $resource, User $user): ResourceScopes|null
    {
        if (!$resource instanceof Resource) {
            $resource = $this->resourceStorage->getOne($resource);
            /** @noinspection CallableParameterUseCaseInTypeContextInspection */
            if ($resource === null) {
                return null;
            }
        }

        // There are no scopes defined for this resource, meaning we can't check on anything.
        if ($resource->getScopes() === null) {
            return null;
        }

        $localKey = $user->getId() . '.' . $resource->getId();

        // The result of "remember" might be "NULL", so we need to check for "array_key_exists" instead of "isset".
        if (array_key_exists($localKey, $this->grantedScopes)) {
            return $this->grantedScopes[$localKey];
        }

        return $this->grantedScopes[$localKey] ??= $this->cache->remember(
            'keycloak.client.permissions.' . $localKey,
            valueGenerator: function () use ($resource, $user) {
                // The user is the owner of the resource, so they have all permissions
                if ($resource->getOwner()->getId() === $user->getId()) {
                    return $resource->getScopes();
                }

                return $this->api->fetchGrantedResourceScopesForUser($resource, $user);
            },
            valueToCache: fn(ResourceScopes|null $scopes) => $scopes === null ? false : $scopes->jsonSerialize(),
            cacheToValue: fn(array|false $scopes) => $scopes === false ? null : new ResourceScopes(...$scopes),
        );
    }

    /**
     * Removes all resolved profiles and will fetch them again the next time they are requested.
     * @return void
     * @internal This method is not part of the public API and should not be used by client code.
     */
    public function flushResolved(): void
    {
        $this->grantedScopes = [];
    }
}
