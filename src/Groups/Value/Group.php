<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Groups\Value;


use Hawk\AuthClient\Util\Validator;

readonly class Group implements \Stringable, \JsonSerializable
{
    private string $id;
    private string $name;
    private string $path;
    private GroupList $children;

    public function __construct(
        string    $id,
        string    $name,
        string    $path,
        GroupList $children
    )
    {
        Validator::requireUuid($id);
        $this->id = $id;
        $this->name = $name;
        $this->path = $path;
        $this->children = $children;
    }

    /**
     * Returns the unique UUID of the group.
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Returns the name of the group. The name does not have to be unique.
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the path of the group. The path is a unique identifier for the group.
     * The path always starts with a slash and is separated by slashes. The path always display the full path to the group.
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Returns the children of the group.
     * @return GroupList
     */
    public function getChildren(): GroupList
    {
        return $this->children;
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return $this->path;
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'path' => $this->path,
            'children' => $this->children
        ];
    }
}
