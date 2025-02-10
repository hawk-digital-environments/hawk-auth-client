<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Util;


use Hawk\AuthClient\Exception\InvalidUuidException;

/**
 * @internal This class is not part of the public API and may change at any time.
 */
class Validator
{
    /**
     * Check if the given string is a valid UUID.
     * @param string $uuid The UUID to validate.
     * @return bool
     */
    public static function isUuid(string $uuid): bool
    {
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid) === 1) {
            $dedashed = str_replace('-', '', $uuid);

            // Values containing only letters or only numbers are not valid UUIDs
            if (ctype_alpha($dedashed) || ctype_digit($dedashed)) {
                // NIL and FFFF UUIDs are valid
                return $dedashed === str_repeat('0', 32) || $dedashed === str_repeat('f', 32);
            }

            return true;
        }

        return false;
    }

    /**
     * Similar to {@see self::isUuid()} but throws an exception if the string is not a valid UUID.
     * @param string $uuid The UUID to validate.
     * @return void
     */
    public static function requireUuid(string $uuid): void
    {
        if (!self::isUuid($uuid)) {
            throw new InvalidUuidException($uuid);
        }
    }
}
