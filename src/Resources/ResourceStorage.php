<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Resources;


use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Layers\ResourceLayerInterface;
use Hawk\AuthClient\Resources\Value\Resource;
use Hawk\AuthClient\Resources\Value\ResourceBuilder;
use Hawk\AuthClient\Resources\Value\ResourceConstraints;
use Hawk\AuthClient\Resources\Value\ResourceList;
use Hawk\AuthClient\Users\UserStorage;
use Hawk\AuthClient\Users\Value\User;
use Hawk\AuthClient\Util\Validator;

class ResourceStorage implements ResourceLayerInterface
{
    protected ResourceCache $cache;
    protected KeycloakApiClient $api;
    protected UserStorage $userStorage;

    public function __construct(
        ResourceCache     $cache,
        KeycloakApiClient $api,
        UserStorage       $userStorage
    )
    {
        $this->cache = $cache;
        $this->api = $api;
        $this->userStorage = $userStorage;
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function getOne(string|\Stringable $identifier): Resource|null
    {
        $id = $this->cache->getResourceId((string)$identifier);
        return $id === null ? null : $this->cache->getOne($id);
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function getAll(?ResourceConstraints $constraints = null): iterable
    {
        return new ResourceList(
            fn() => $this->cache->getResourceIdStream($constraints),
            [$this->cache, 'getAllByIds']
        );
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function define(string|\Stringable|Resource $identifier): ResourceBuilder
    {
        if ($identifier instanceof Resource) {
            $resource = $identifier;
        } else {
            $resource = $this->getOne((string)$identifier);
        }

        if (!$resource && Validator::isUuid($identifier)) {
            $name = 'not-named-resource-' . hash('sha256', $identifier);
        } else if ($identifier instanceof Resource) {
            $name = $identifier->getName();
        } else {
            $name = (string)$identifier;
        }

        return new ResourceBuilder($resource, $this->userStorage, $name, $this->api, $this->cache);
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function remove(Resource $resource): void
    {
        $this->cache->remove($resource->getId());
        $this->api->removeResource($resource);
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function shareWithUser(Resource $resource, User $user, array|null $scopes): void
    {
        $this->api->setResourceUserPermissions($resource, $user, $scopes);
    }
}
