<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Resources\Value;


use Hawk\AuthClient\Exception\ResourceOwnerNotFoundException;
use Hawk\AuthClient\Users\UserStorage;
use Hawk\AuthClient\Users\Value\User;
use Hawk\AuthClient\Util\Uuid;

class Resource implements \JsonSerializable
{
    protected Uuid $id;
    protected string $name;
    protected string|null $displayName;
    protected Uuid $ownerId;
    protected bool $isUserManaged;
    protected array $attributes;
    protected string|null $iconUri;
    protected array $uris;
    protected ResourceScopes|null $scopes;
    protected UserStorage $userStorage;
    protected ?string $type;

    public function __construct(
        Uuid $id,
        string              $name,
        string|null         $displayName,
        Uuid $ownerId,
        bool                $isUserManaged,
        array|null          $attributes,
        string|null         $iconUri,
        array|null          $uris,
        ResourceScopes|null $scopes,
        string|null         $type,
        UserStorage         $userStorage
    )
    {
        $this->id = $id;
        $this->name = $name;
        $this->displayName = $displayName;
        $this->ownerId = $ownerId;
        $this->isUserManaged = $isUserManaged;
        $this->attributes = $attributes ?? [];
        $this->iconUri = empty($iconUri) ? null : $iconUri;
        $this->uris = $uris ?? [];
        $this->scopes = $scopes;
        $this->type = empty($type) ? null : $type;
        $this->userStorage = $userStorage;
    }

    /**
     * Returns the resource's unique UUID.
     * @return Uuid
     */
    public function getId(): Uuid
    {
        return $this->id;
    }

    /**
     * Returns the resource's name. It is an additional, unique identifier for the resource.
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the resource's display name. It is a human-readable name for the resource.
     * It does not have to be unique. If not set, the name will be returned.
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->displayName ?? $this->name;
    }

    /**
     * Returns the user that owns the resource.
     * @return User
     */
    public function getOwner(): User
    {
        $owner = $this->userStorage->getOne($this->ownerId);

        if ($owner === null) {
            throw new ResourceOwnerNotFoundException($this->ownerId);
        }

        return $owner;
    }

    /**
     * Returns true if the resource is managed by a certain user of the realm,
     * false if the resource belongs to the realm itself.
     * Keycloak calls the distinction UMA and non-UMA resources.
     * @return bool
     */
    public function isUserManaged(): bool
    {
        return $this->isUserManaged;
    }

    /**
     * Returns the attributes of the resource.
     * Attributes are additional information about the resource.
     * @return array|null
     */
    public function getAttributes(): array|null
    {
        return empty($this->attributes) ? null : array_map(static fn($value) => $value[0], $this->attributes);
    }

    /**
     * Returns the value of the attribute with the given key.
     * If the attribute is not set, the default value will be returned.
     * @param string $key The key of the attribute
     * @param mixed $default The default value to return if the attribute is not set
     * @return mixed
     */
    public function getAttribute(string $key, mixed $default = null): mixed
    {
        if (is_array($this->attributes[$key] ?? null)) {
            return $this->attributes[$key][0] ?? $default;
        }

        return $default;
    }

    /**
     * Returns the URI of the resource's icon.
     * @return string|null
     */
    public function getIconUri(): string|null
    {
        return $this->iconUri;
    }

    /**
     * Returns the URIs of the resource.
     * URIS that provides the locations/addresses for the resource. For HTTP resources, the URIS are
     * usually the relative paths used to serve these resources.
     * @return array|null
     */
    public function getUris(): array|null
    {
        return empty($this->uris) ? null : $this->uris;
    }

    /**
     * Returns a list of scopes that the resource is associated with.
     * Scopes define what can be done with the resource.
     *
     * Examples of scopes are view, edit, delete, and so on. However, scope can also be related to specific
     * information provided by a resource. In this case, you can have a project resource and a cost scope,
     * where the cost scope is used to define specific policies and permissions for users to access a project’s cost.
     *
     * @return ResourceScopes|null
     */
    public function getScopes(): ResourceScopes|null
    {
        return $this->scopes;
    }

    /**
     * Returns the type of the resource.
     * Types can be used to categorize resources.
     *
     * @return string|null
     */
    public function getType(): string|null
    {
        return $this->type;
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function jsonSerialize(): array
    {
        return [
            'id' => (string)$this->id,
            'name' => $this->name,
            'displayName' => $this->displayName,
            'owner' => $this->ownerId,
            'isUserManaged' => $this->isUserManaged,
            'attributes' => $this->attributes,
            'iconUri' => $this->iconUri,
            'uris' => $this->uris,
            'scopes' => $this->scopes?->jsonSerialize() ?? [],
            'type' => $this->type
        ];
    }
}
