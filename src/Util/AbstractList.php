<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Util;

/**
 * @template T of object
 * @implements \IteratorAggregate<T>
 */
abstract class AbstractList implements \IteratorAggregate, \Countable, \JsonSerializable
{
    /**
     * @var T[]
     */
    protected array $items;

    /**
     * @return \ArrayIterator<T>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function jsonSerialize(): array
    {
        return $this->items;
    }
}
