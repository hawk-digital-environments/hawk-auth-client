<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Resources;


use Hawk\AuthClient\Resources\Value\Resource;
use Hawk\AuthClient\Resources\Value\ResourceScopes;
use Hawk\AuthClient\Users\Value\UserContext;

class ResourceFactory
{
    protected UserContext $userContext;

    public function __construct(
        UserContext $userContext
    )
    {
        $this->userContext = $userContext;
    }

    public function makeResourceFromKeycloakData(array $data): Resource
    {
        return new Resource(
            $data['_id'],
            $data['name'],
            $data['displayName'] ?? null,
            $data['owner']['id'] ?? '',
            (bool)$data['ownerManagedAccess'],
            $data['attributes'] ?? [],
            $data['icon_uri'] ?? '',
            $data['uris'] ?? [],
            is_array($data['scopes'] ?? null)
                ? new ResourceScopes(...array_map(fn($scope) => $scope['name'], $data['scopes']))
                : null,
            $data['type'] ?? null,
            $this->userContext->getStorage()
        );
    }

    public function makeResourceFromCacheData(array $data): Resource
    {
        return new Resource(
            $data['id'],
            $data['name'],
            $data['displayName'] ?? null,
            $data['owner'],
            $data['isUserManaged'],
            $data['attributes'],
            $data['iconUri'],
            $data['uris'],
            empty($data['scopes']) ? null : new ResourceScopes(...$data['scopes']),
            $data['type'],
            $this->userContext->getStorage()
        );
    }
}
