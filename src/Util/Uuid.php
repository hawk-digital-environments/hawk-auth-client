<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Util;


use Hawk\AuthClient\Exception\InvalidUuidException;
use Stringable;

/**
 * @internal This class is not part of the public API and may change at any time.
 */
readonly class Uuid implements \Stringable, \JsonSerializable
{
    protected string $value;

    public function __construct(string|\Stringable $uuid)
    {
        if (!self::isValid($uuid)) {
            throw new InvalidUuidException((string)$uuid);
        }
        $this->value = (string)$uuid;
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function jsonSerialize(): string
    {
        return $this->value;
    }

    /**
     * Check if the given string is a valid UUID.
     * @param string|Stringable $value The UUID to validate.
     * @return bool
     */
    public static function isValid(string|\Stringable $value): bool
    {
        if ($value instanceof static) {
            return true;
        }

        $value = (string)$value;

        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value) === 1) {
            $dedashed = str_replace('-', '', $value);

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
     * Create a new UUID from a string or a UUID object.
     * @param Uuid|string|\Stringable $uuid The UUID to convert.
     * @return Uuid The UUID object created from the given value, or the given value if it was already a UUID object.
     */
    public static function fromOne(self|string|\Stringable $uuid): Uuid
    {
        return $uuid instanceof self ? $uuid : new self($uuid);
    }

    /**
     * Create a list of UUIDs from a list of strings or UUID objects.
     * The resulting list will contain only unique UUIDs.
     * @param Uuid[]|string[]|\Stringable[] $uuids The UUIDs to convert.
     * @return Uuid[] The UUID objects created from the given values, or the given values if they were already UUID objects.
     */
    public static function fromList(self|string|\Stringable ...$uuids): array
    {
        $result = [];
        $knownUuids = [];
        foreach ($uuids as $uuid) {
            $uuid = self::fromOne($uuid);
            if (in_array((string)$uuid, $knownUuids, true)) {
                continue;
            }
            $result[] = $uuid;
            $knownUuids[] = (string)$uuid;
        }
        return $result;
    }
}
