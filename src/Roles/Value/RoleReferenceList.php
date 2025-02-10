<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Roles\Value;


use Hawk\AuthClient\Roles\RoleReferenceTypeEnum;
use Hawk\AuthClient\Util\AbstractList;

/**
 * @extends AbstractList<RoleReference>
 */
class RoleReferenceList extends AbstractList
{
    public function __construct(RoleReference ...$roleReferences)
    {
        $this->items = $roleReferences;
    }

    /**
     * Checks if the list contains any of the given roles. The roles can be given as string, {@see RoleReference} or {@see Role}.
     * @param string|\Stringable|RoleReference|Role ...$roles
     * @return bool
     */
    public function hasAny(string|\Stringable|RoleReference|Role ...$roles): bool
    {
        foreach ($roles as $givenRole) {
            // Special handling if a RoleInterface is given, because we can have more options to compare on
            if ($givenRole instanceof Role) {
                foreach ($this->items as $ref) {
                    if ($ref->getType() === RoleReferenceTypeEnum::ID && (string)$ref === $givenRole->getId()) {
                        return true;
                    }
                    if ($ref->getType() === RoleReferenceTypeEnum::NAME && (string)$ref === $givenRole->getName()) {
                        return true;
                    }
                }
                continue;
            }

            // Default handling
            $givenRole = $givenRole instanceof RoleReference ? $givenRole : new RoleReference((string)$givenRole);
            foreach ($this->items as $roleRefA) {
                if ((string)$roleRefA === (string)$givenRole && $roleRefA->getType() === $givenRole->getType()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Creates a new instance from a list of strings.
     * @param string ...$values
     * @return self
     * @internal This method is intended for internal use only and should not be used outside the library.
     */
    public static function fromScalarList(string ...$values): self
    {
        return new self(...array_map(static fn(string $value) => new RoleReference($value), $values));
    }
}
