<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Resources\Value;


use Hawk\AuthClient\Users\Value\User;

class ResourceConstraints
{
    protected string|null $name = null;
    protected bool $exactNames = false;
    protected string|null $uri = null;
    protected string|null $owner = null;
    protected bool $sharedOnly = false;
    protected string|null $sharedWith = null;
    protected string|null $type = null;
    protected array $ids = [];

    /**
     * Returns the value of the "name" filter to apply to the resources.
     * @return string|null
     * @see isExactNames() to check if the filter should be applied exactly, otherwise it will be a partial match.
     */
    public function getName(): string|null
    {
        return $this->name;
    }

    /**
     * Sets a filter to only return resources with the given name.
     * Can not be used in combination with the "ids" filter!
     * @param string|null $name The name of the resources to return.
     * @param bool|null $exactNames If true, the filter will be applied exactly, otherwise it will be a partial match.
     * @return $this
     */
    public function withName(string|null $name, bool|null $exactNames = null): static
    {
        $clone = clone $this;
        $clone->name = empty($name) ? null : $name;

        if ($clone->name === null) {
            $clone->exactNames = false;
        } elseif ($exactNames !== null) {
            $clone->exactNames = $exactNames;
        }

        return $clone;
    }

    /**
     * Returns whether the "name" filter should be applied exactly.
     * @return bool True if the filter should be applied exactly, otherwise it will be a partial match.
     */
    public function isExactNames(): bool
    {
        return $this->exactNames;
    }

    /**
     * Returns the value of the "uri" filter to apply to the resources.
     * @return string|null
     */
    public function getUri(): string|null
    {
        return $this->uri;
    }

    /**
     * Sets a filter to only return resources with the given uri.
     * @param string|null $uri The uri of the resources to return.
     * @return $this
     */
    public function withUri(string|null $uri): static
    {
        $clone = clone $this;
        $clone->uri = empty($uri) ? null : $uri;
        return $clone;
    }

    /**
     * Returns the value of the "owner" filter to apply to the resources.
     * If it is set, this value should be the uuid of the owner.
     * @return string|null
     */
    public function getOwner(): string|null
    {
        return $this->owner;
    }

    /**
     * Sets a filter to only return resources owned by the given user.
     * If the owner is null, the filter is removed.
     * This filter can be combined with the "sharedOnly" filter, which will only return resources OWNED AND SHARED by the user.
     * @param string|\Stringable|User|null $owner The owner of the resources to return.
     * @param bool|null $sharedOnly If true, only shared resources will be returned.
     * @return $this
     */
    public function withOwner(string|\Stringable|User|null $owner, bool|null $sharedOnly = null): static
    {
        if ($owner instanceof User) {
            $owner = $owner->getId();
        }

        $clone = clone $this;
        $clone->owner = empty($owner) ? null : (string)$owner;

        if ($clone->owner === null) {
            $clone->sharedOnly = false;
        } elseif ($sharedOnly !== null) {
            $clone->sharedOnly = $sharedOnly;
        }

        return $clone;
    }

    /**
     * Returns whether the "sharedOnly" filter is set.
     * @return bool True if the filter is set, otherwise false.
     */
    public function isSharedOnly(): bool
    {
        return $this->sharedOnly;
    }

    /**
     * Returns the value of the "sharedWith" filter to apply to the resources.
     * If it is set, this value should be the uuid of the user the resource is shared with.
     * @return string|null
     */
    public function getSharedWith(): string|null
    {
        return $this->sharedWith;
    }

    /**
     * Sets a filter to only return resources shared with the given user.
     * If the sharedWith is null, the filter is removed.
     * @param string|\Stringable|User|null $sharedWith The user the resources are shared with.
     * @return $this
     */
    public function withSharedWith(string|\Stringable|User|null $sharedWith): static
    {
        if ($sharedWith instanceof User) {
            $sharedWith = $sharedWith->getId();
        }

        $clone = clone $this;
        $clone->sharedWith = empty($sharedWith) ? null : (string)$sharedWith;
        return $clone;
    }

    /**
     * Returns the value of the "type" filter to apply to the resources.
     * @return string|null
     */
    public function getType(): string|null
    {
        return $this->type;
    }

    /**
     * Sets a filter to only return resources with the given type.
     * @param string|null $type The type of the resources to return.
     * @return $this
     */
    public function withType(string|null $type): static
    {
        $clone = clone $this;
        $clone->type = empty($type) ? null : $type;
        return $clone;
    }

    /**
     * Returns the list of resource ids that are set as filters.
     * @return array
     */
    public function getIds(): array
    {
        return $this->ids;
    }

    /**
     * Sets a filter to only return resources with the given ids.
     * If the filter is set, the "name", "owner", "type" and "uri" filters are ignored.
     * Exception owner + sharedOnly, this is still working!
     *
     * @param string|\Stringable ...$ids The ids of the resources to return.
     * @return $this
     */
    public function withIds(string|\Stringable ...$ids): static
    {
        $clone = clone $this;
        $clone->ids = array_values(array_unique(array_map('strval', $ids)));
        return $clone;
    }
}
