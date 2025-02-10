<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Roles\Value;


use Hawk\AuthClient\Util\AbstractList;

/**
 * @extends AbstractList<Role>
 */
class RoleList extends AbstractList
{
    public function __construct(Role ...$roles)
    {
        $this->items = $roles;
    }

    /**
     * Creates a new instance from a list of nested arrays
     * @param array ...$values
     * @return self
     * @internal This method is intended for internal use only and should not be used outside the library.
     */
    public static function fromScalarList(array ...$values): self
    {
        return new self(...array_map(static fn(array $value) => Role::fromArray($value), $values));
    }
}
