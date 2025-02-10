<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Profiles\Structure\Util;


use Hawk\AuthClient\Keycloak\Value\ConnectionConfig;
use Stringable;

/**
 * @internal This trait is not part of the public API and may change without notice.
 */
trait ProfilePrefixTrait
{
    protected const string MARKER_PREFIX = 'hawk.';

    protected ConnectionConfig $config;

    /**
     * Determines if the provided name is a full name or a local name.
     * Full names are prefixed with our internal {@see MARKER_PREFIX}.
     * Everything else is considered a local/short name, EXCEPT when the client id is explicitly set to false, meaning
     * that we are dealing with a global field, which is always a full name.
     *
     * @param string|Stringable $name The name to check, e.g. "username" or "hawk.client-id.username"
     * @param false|string|Stringable|null $clientId The client id to use as prefix. If set to false, the name is considered a global field.
     * @return bool
     */
    protected function isFullName(string|\Stringable $name, false|null|string|\Stringable $clientId = null): bool
    {
        if ($clientId === false) {
            return true;
        }

        return str_starts_with((string)$name, static::MARKER_PREFIX);
    }

    /**
     * Returns the full name of the provided name.
     * If the name is already a full name, it is returned as is.
     * If the name is a local name, it is prefixed with the client id.
     *
     * @param string|Stringable $name The name to get the full name for, e.g. "username" or "hawk.client-id.username"
     * @param false|string|Stringable|null $clientId The client id to use as prefix. If set to false, the name is considered a global field.
     * @return string
     */
    protected function getFullName(string|\Stringable $name, false|null|string|\Stringable $clientId = null): string
    {
        if ($this->isFullName($name)) {
            return (string)$name;
        }

        return $this->getPrefix($clientId) . $name;
    }

    /**
     * The opposite of {@see getFullName()}. Returns the local name of the provided full name.
     * Note, foreign names (full names with a different client id) will be kept as is.
     * @param string|\Stringable $fullName
     * @param false|string|Stringable|null $clientId
     * @return string
     */
    protected function getLocalName(string|\Stringable $fullName, false|null|string|\Stringable $clientId = null): string
    {
        $fullNameString = (string)$fullName;
        $prefixToStrip = $this->getPrefix($clientId);
        if ($prefixToStrip === '') {
            // Global field -> keep as is
            return $fullNameString;
        }

        if (str_starts_with($fullNameString, $prefixToStrip)) {
            // Local field -> strip prefix
            return substr($fullNameString, strlen($prefixToStrip));
        }

        // Foreign field -> keep as is
        return $fullNameString;
    }

    /**
     * Determines if the provided full name belongs to the given client id.
     * @param string|Stringable $fullName
     * @param false|string|Stringable|null $clientId
     * @return bool
     */
    protected function belongsTo(string|\Stringable $fullName, false|null|string|\Stringable $clientId): bool
    {
        if ($clientId === false) {
            return !str_starts_with((string)$fullName, static::MARKER_PREFIX);
        }

        return str_starts_with((string)$fullName, $this->getPrefix($clientId));
    }

    /**
     * Generates the correct prefix for the given client id.
     * If the client id is false, the prefix will be empty.
     * If the client id is null, the current client id will be used.
     * Otherwise, the client id given will be used as prefix.
     * The prefix will look like this: "hawk.client-id."
     * @param false|string|Stringable|null $clientId
     * @return string
     */
    protected function getPrefix(false|null|string|\Stringable $clientId = null): string
    {
        // false -> Keep name as is (global element)
        if ($clientId === false) {
            return '';
        }

        // null -> Use the current client id as prefix
        if ($clientId === null) {
            return static::MARKER_PREFIX . $this->config->getClientId() . '.';
        }

        // string -> Use the provided prefix
        return static::MARKER_PREFIX . $clientId . '.';
    }
}
