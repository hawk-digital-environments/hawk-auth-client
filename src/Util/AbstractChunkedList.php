<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Util;

use Traversable;

/**
 * Lazy loading list of entities by their ids. This list will load items in chunks of 50.
 * @template T of object
 * @extends \IteratorAggregate<T>
 */
abstract class AbstractChunkedList implements \IteratorAggregate
{
    /**
     * @var callable
     */
    protected $idStreamFactory;
    /**
     * @var callable
     */
    protected $itemStreamFactory;

    /**
     * @param callable(): iterable<string> $idStreamFactory
     * @param callable(string ...): iterable<T> $itemStreamFactory
     */
    public function __construct(callable $idStreamFactory, callable $itemStreamFactory)
    {
        $this->idStreamFactory = $idStreamFactory;
        $this->itemStreamFactory = $itemStreamFactory;
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function getIterator(): Traversable
    {
        $chunkSize = $this->getChunkSize();

        $chunk = [];
        $i = 0;
        foreach ($this->getIdStream() as $userId) {
            $chunk[] = $userId;
            if ($i++ === $chunkSize - 1) {
                yield from $this->fetchItems(...$chunk);
                $chunk = [];
                $i = 0;
            }
        }

        if ($chunk) {
            yield from $this->fetchItems(...$chunk);
        }
    }

    /**
     * Returns the number of items that are loaded in a single chunk.
     * @return int
     */
    protected function getChunkSize(): int
    {
        return 50;
    }

    /**
     * MUST return an iterable of ids that represent the items in the list.
     * @return iterable
     */
    protected function getIdStream(): iterable
    {
        return ($this->idStreamFactory)();
    }

    /**
     * MUST return an iterable of items that correspond to the given ids.
     * If an item does not lead to an entity, it MUST be ignored.
     *
     * @param string ...$ids
     * @return iterable<T>
     */
    protected function fetchItems(string ...$ids): iterable
    {
        return ($this->itemStreamFactory)(...$ids);
    }
}
