<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Users\Value;


use Hawk\AuthClient\Profiles\Value\UserProfile;

class UserConstraints
{
    public const string ATTR_USERNAME = UserProfile::ATTRIBUTE_USERNAME;
    public const string ATTR_EMAIL = UserProfile::ATTRIBUTE_EMAIL;
    public const string ATTR_FIRST_NAME = UserProfile::ATTRIBUTE_FIRST_NAME;
    public const string ATTR_LAST_NAME = UserProfile::ATTRIBUTE_LAST_NAME;

    protected bool $online = false;
    protected bool $enabled = true;
    protected string|null $search = null;
    protected array $attributes = [];
    protected array $ids = [];

    /**
     * Returns true if the filter is set to only return online users.
     * @return bool
     */
    public function onlyOnline(): bool
    {
        return $this->online;
    }

    /**
     * Sets the filter to only return online users. False by default, means all users.
     * "Online users" are users that have been active in ANY client within the last 10 minutes.
     * @param bool $online
     * @return $this
     */
    public function withOnlyOnline(bool $online = true): static
    {
        $clone = clone $this;
        $clone->online = $online;
        return $clone;
    }

    /**
     * Returns the search string to filter users by or null if no search filter is set.
     * @return string|null
     */
    public function getSearch(): string|null
    {
        return $this->search;
    }

    /**
     * Sets a search string to filter users by.
     * String contained in username, first or last name, or email.
     * Default search behavior is prefix-based (e.g., foo or foo*). Use *foo* for infix search and \"foo\" for exact search.
     * If null is passed, the search filter is removed.
     * @param string|null $search
     * @return $this
     */
    public function withSearch(string|null $search): static
    {
        $clone = clone $this;
        $clone->search = empty($search) ? null : $search;
        return $clone;
    }

    /**
     * Returns the list of attributes that are set as filters.
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Sets a filter on a certain attribute. Attributes are key-value pairs with partial match.
     * If the attribute is already set, it will be overwritten. Attributes are any attributes of the user including profile fields.
     *
     * @param string $attribute
     * @param string $value
     * @return $this
     * @see the ATTR_* constants for common attributes
     */
    public function withAttribute(string $attribute, string $value): static
    {
        $clone = clone $this;
        $clone->attributes[$attribute] = $value;
        return $clone;
    }

    /**
     * Removes a filter on a certain attribute.
     * If the attribute is not set, nothing happens.
     *
     * @param string $attribute
     * @return $this
     */
    public function withoutAttribute(string $attribute): static
    {
        $clone = clone $this;
        unset($clone->attributes[$attribute]);
        return $clone;
    }

    /**
     * Returns the list of user ids that are set as filters.
     * @return array
     */
    public function getIds(): array
    {
        return $this->ids;
    }

    /**
     * Sets a filter to only return users with the given ids.
     * If the filter is set, "search" and "attributes" filters are ignored. The online filter is still applied!
     *
     * @param string|\Stringable ...$ids The ids of the users to return.
     * @return $this
     */
    public function withIds(string|\Stringable ...$ids): static
    {
        $clone = clone $this;
        $clone->ids = array_values(array_unique(array_map('strval', $ids)));
        return $clone;
    }
}
