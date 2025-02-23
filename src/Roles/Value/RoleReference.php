<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Roles\Value;


use Hawk\AuthClient\Roles\RoleReferenceTypeEnum;
use Hawk\AuthClient\Util\Uuid;

readonly class RoleReference implements \Stringable, \JsonSerializable
{
    protected string $value;

    public function __construct(string $role)
    {
        $this->value = $role;
    }

    /**
     * Returns the type of the role reference. It can be either an ID or a name.
     * @return RoleReferenceTypeEnum
     */
    public function getType(): RoleReferenceTypeEnum
    {
        if (Uuid::isValid($this->value)) {
            return RoleReferenceTypeEnum::ID;
        }

        return RoleReferenceTypeEnum::NAME;
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
}
