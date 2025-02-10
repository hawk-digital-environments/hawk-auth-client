<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Resources\Value;


use Traversable;

readonly class ResourceScopes implements \JsonSerializable, \IteratorAggregate
{
    protected array $scopes;

    public function __construct(string ...$scopes)
    {
        $this->scopes = array_unique($scopes);
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->scopes);
    }

    /**
     * Checks if this permission has any of the given scopes.
     *
     * @param string ...$scopes Names of the scopes to check for. If this list is empty, this method will always return true.
     * @return bool
     */
    public function hasAny(string ...$scopes): bool
    {
        if (empty($scopes)) {
            return true;
        }

        return count(array_intersect($this->scopes, func_get_args())) > 0;
    }

    /**
     * Checks if this permission has all given scopes.
     *
     * @param string ...$scopes The names of the scopes to check for. If this list is empty, this method will always return false.
     * @return bool
     */
    public function hasAll(string ...$scopes): bool
    {
        if (empty($scopes)) {
            return false;
        }

        return count(array_diff(func_get_args(), $this->scopes)) === 0;
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function jsonSerialize(): array
    {
        return $this->scopes;
    }
}
