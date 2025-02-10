<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Users\Value;


use Traversable;

class UserClaims implements \JsonSerializable, \IteratorAggregate
{
    private array $list;

    public function __construct(array $list)
    {
        $this->list = $list;
    }

    /**
     * Checks if the claims list contains the given key.
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->list[$key]);
    }

    /**
     * Returns the value of the given key. If the key does not exist, the default value is returned.
     * @param string $key The key to get the value for.
     * @param mixed|null $default The default value to return if the key does not exist.
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->list[$key] ?? $default;
    }

    /**
     * Returns all keys in the claims list.
     * @return array
     */
    public function getKeys(): array
    {
        return array_keys($this->list);
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->list);
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function jsonSerialize(): array
    {
        return $this->list;
    }
}
