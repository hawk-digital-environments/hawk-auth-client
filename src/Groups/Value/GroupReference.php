<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Groups\Value;


use Hawk\AuthClient\Groups\GroupReferenceTypeEnum;
use Hawk\AuthClient\Util\Uuid;

class GroupReference implements \Stringable, \JsonSerializable
{
    private string $value;

    public function __construct(string $group)
    {
        $this->value = $group;
    }

    /**
     * Returns the type of the group reference. It can be either an ID, a name or a path.
     * @return GroupReferenceTypeEnum
     */
    public function getType(): GroupReferenceTypeEnum
    {
        if (Uuid::isValid($this->value)) {
            return GroupReferenceTypeEnum::ID;
        }

        if (str_starts_with($this->value, '/')) {
            return GroupReferenceTypeEnum::PATH;
        }

        return GroupReferenceTypeEnum::NAME;
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
