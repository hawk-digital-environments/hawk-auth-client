<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Roles\Value;


use Hawk\AuthClient\Util\Validator;

readonly class Role implements \JsonSerializable, \Stringable
{
    private string $id;
    private string $name;
    private bool $isClientRole;
    private string|null $description;
    private array $attributes;

    public function __construct(
        string      $id,
        string      $name,
        bool        $isClientRole,
        string|null $description,
        array       $attributes
    )
    {
        Validator::requireUuid($id);
        $this->id = $id;
        $this->name = $name;
        $this->isClientRole = $isClientRole;
        $this->description = $description;
        $this->attributes = $attributes;
    }

    /**
     * Returns the unique UUID of the role.
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Returns the name of the role. The name does not have to be unique.
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns true if the role is defined on the client (application) level.
     * If this is false the role is defined on the realm level.
     *
     * @return bool
     */
    public function isClientRole(): bool
    {
        return $this->isClientRole;
    }

    /**
     * Returns the description of the role.
     * @return string|null
     */
    public function getDescription(): string|null
    {
        return $this->description;
    }

    /**
     * Returns all defined attributes of the role.
     * Attributes are generic key-value pairs that can be used to store additional information.
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Returns the value of the attribute with the given key.
     * If the attribute does not exist, the default value will be returned.
     *
     * @param string $key The key of the attribute
     * @param mixed $default The default value to return if the attribute does not exist
     * @return mixed
     */
    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'isClientRole' => $this->isClientRole,
            'description' => $this->description,
            'attributes' => $this->attributes,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            $data['name'],
            $data['isClientRole'],
            $data['description'],
            $data['attributes']
        );
    }
}
