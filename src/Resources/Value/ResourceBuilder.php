<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Resources\Value;


use Hawk\AuthClient\Exception\NoResourceIdAssignedException;
use Hawk\AuthClient\Exception\NoResourceOwnerAssignedException;
use Hawk\AuthClient\Exception\ResourceNameMissingWhenCreatingResourceBuilderException;
use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Resources\ResourceCache;
use Hawk\AuthClient\Users\UserStorage;
use Hawk\AuthClient\Users\Value\User;
use Hawk\AuthClient\Util\Uuid;

class ResourceBuilder extends Resource
{
    protected const string EMPTY_UUID = '00000000-0000-0000-0000-000000000000';

    protected bool $updatesExistingResource = false;
    protected bool $dirty = false;
    protected KeycloakApiClient $api;
    protected ResourceCache $cache;

    public function __construct(
        Resource|null     $resource,
        UserStorage       $userStorage,
        string|null       $name,
        KeycloakApiClient $api,
        ResourceCache     $cache
    )
    {
        if ($resource) {
            parent::__construct(
                $resource->getId(),
                $resource->getName(),
                $resource->getDisplayName(),
                $resource->ownerId,
                $resource->isUserManaged(),
                $resource->getAttributes(),
                $resource->getIconUri(),
                $resource->getUris(),
                $resource->getScopes(),
                $resource->getType(),
                $userStorage
            );
            $this->updatesExistingResource = true;
        } else {
            if (empty($name)) {
                throw new ResourceNameMissingWhenCreatingResourceBuilderException();
            }
            parent::__construct(
                id: new Uuid(self::EMPTY_UUID),
                name: $name,
                displayName: null,
                ownerId: new Uuid(self::EMPTY_UUID),
                isUserManaged: false,
                attributes: null,
                iconUri: null,
                uris: null,
                scopes: null,
                type: null,
                userStorage: $userStorage);
            $this->dirty = true;
        }

        $this->api = $api;
        $this->cache = $cache;
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function getId(): Uuid
    {
        if (!$this->updatesExistingResource) {
            throw new NoResourceIdAssignedException();
        }

        return parent::getId();
    }

    /**
     * Returns true if the builder is updating an existing resource. When false is returned, the builder is creating a new resource.
     * Note: While this method returns false, {@see getId()} will throw an exception.
     * @return bool
     */
    public function doesUpdateExistingResource(): bool
    {
        return $this->updatesExistingResource;
    }

    /**
     * Defines the name of the resource. It is an additional, unique identifier for the resource.
     * @param string $name
     * @return $this
     */
    public function setName(string $name): static
    {
        if ($this->name !== $name) {
            $this->name = $name;
            $this->dirty = true;
        }
        return $this;
    }

    /**
     * Defines the display name of the resource. It is a human-readable name for the resource.
     * It does not have to be unique. If not set, the name will be used.
     * @param string $displayName
     * @return $this
     */
    public function setDisplayName(string $displayName): static
    {
        if ($this->displayName !== $displayName) {
            $this->displayName = $displayName;
            $this->dirty = true;
        }
        return $this;
    }

    /**
     * Defines the owner of the resource. The owner is the user who manages the resource.
     * When setting the owner, the resource will be marked as user-managed.
     * @param User $owner
     * @return $this
     */
    public function setOwner(User $owner): static
    {
        if ($this->ownerId !== $owner->getId()) {
            $this->ownerId = $owner->getId();
            $this->isUserManaged = true;
            $this->dirty = true;
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function getOwner(): User
    {
        if ((string)$this->ownerId === self::EMPTY_UUID) {
            throw new NoResourceOwnerAssignedException();
        }

        return parent::getOwner();
    }

    /**
     * Defines a single attribute of the resource.
     * Attributes are additional information about the resource. They are key-value pairs, which do not follow a specific schema.
     * @param string $key The key of the attribute
     * @param string $value The value of the attribute
     * @return $this
     */
    public function setAttribute(string $key, string $value): static
    {
        if (($this->attributes[$key][0] ?? null) !== $value) {
            $this->attributes[$key] = [$value];
            $this->dirty = true;
        }
        return $this;
    }

    /**
     * Removes a single attribute from the resource.
     * @param string $key The key of the attribute
     * @return $this
     */
    public function removeAttribute(string $key): static
    {
        if (isset($this->attributes[$key])) {
            unset($this->attributes[$key]);
            $this->dirty = true;
        }
        return $this;
    }

    /**
     * Defines the icon URI of the resource.
     * The icon URI is a URL to an image that represents the resource.
     * @param string|null $iconUri
     * @return $this
     */
    public function setIconUri(string|null $iconUri): static
    {
        $iconUri = empty($iconUri) ? null : $iconUri;
        if ($this->iconUri !== $iconUri) {
            $this->iconUri = $iconUri;
            $this->dirty = true;
        }
        return $this;
    }

    /**
     * Adds a single uri to the resource.
     * URIS that provides the locations/addresses for the resource. For HTTP resources, the URIS are
     * usually the relative paths used to serve these resources.
     * May be called multiple times to add multiple URIs.
     * @return $this
     */
    public function addUri(string $uri): static
    {
        if (!in_array($uri, $this->uris, true)) {
            $this->dirty = true;
            $this->uris[] = $uri;
        }
        return $this;
    }

    /**
     * Removes a single uri from the resource.
     * @return $this
     */
    public function removeUri(string $uri): static
    {
        $index = array_search($uri, $this->uris, true);
        if ($index !== false) {
            $this->dirty = true;
            unset($this->uris[$index]);
        }
        return $this;
    }

    /**
     * Adds a new scope to the resource.
     * Scopes define what can be done with the resource.
     *
     * Examples of scopes are view, edit, delete, and so on. However, scope can also be related to specific
     * information provided by a resource. In this case, you can have a project resource and a cost scope,
     * where the cost scope is used to define specific policies and permissions for users to access a project’s cost.
     *
     * IMPORTANT: Scopes that do not exist in the Keycloak client, will be automatically registered!
     *
     * @param string $scope The scope to add
     * @param string ...$scopes Additional scopes to add
     * @return $this
     * @see addScope() to add a single scope
     */
    public function addScope(string $scope, string ...$scopes): static
    {
        if (!$this->scopes?->hasAny($scope)) {
            $this->dirty = true;
            $this->scopes = $this->scopes === null
                ? new ResourceScopes($scope)
                : new ResourceScopes(...[...$this->scopes, $scope]);
        }

        if (!empty($scopes)) {
            $this->addScope(...$scopes);
        }

        return $this;
    }

    /**
     * Removes a single scope from the resource.
     * @param string $scope
     * @param string ...$scopes
     * @return $this
     */
    public function removeScope(string $scope, string ...$scopes): static
    {
        if ($this->scopes?->hasAny($scope)) {
            $this->dirty = true;
            $this->scopes = new ResourceScopes(...array_filter([...$this->scopes], static fn($s) => $s !== $scope));
        }

        if (!empty($scopes)) {
            $this->removeScope(...$scopes);
        }

        return $this;
    }

    /**
     * Defines the type of the resource.
     * Types can be used to categorize resources.
     * @param string|null $type
     * @return $this
     */
    public function setType(string|null $type): static
    {
        $type = empty($type) ? null : $type;
        if ($this->type !== $type) {
            $this->type = $type;
            $this->dirty = true;
        }
        return $this;
    }

    /**
     * Saves the resource to the API.
     * If the resource is new, it will be created. If it already exists, it will be updated.
     * If the resource already existed, but no changes were made, the method will do nothing.
     * @see self::doesUpdateExistingResource() to check if the resource is new or existing
     */
    public function save(): void
    {
        if ($this->dirty) {
            if ($this->updatesExistingResource) {
                $this->cache->remove($this->id);
            }
            $this->api->upsertResource($this);
            $this->dirty = false;
        }
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function jsonSerialize(): array
    {
        $args = [
            'name' => $this->name,
            'displayName' => $this->displayName ?? $this->name,
            'ownerManagedAccess' => $this->isUserManaged,
            'attributes' => $this->attributes,
            'icon_uri' => $this->iconUri ?? '',
            'uris' => $this->uris,
            'scopes' => $this->scopes?->jsonSerialize() ?? [],
            'type' => $this->type ?? '',
        ];

        if (!empty($this->ownerId) && $this->isUserManaged) {
            $args['owner'] = $this->ownerId;
        }

        return $args;
    }
}
