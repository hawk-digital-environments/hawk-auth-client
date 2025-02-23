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

    protected int $offset = 0;
    protected int $limit = 0;

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
        $loadedItems = 0;
        $returnedItems = 0;
        foreach ($this->getIdStream() as $userId) {
            if ($loadedItems++ < $this->offset) {
                continue;
            }

            $chunk[] = $userId;

            if ($this->limit > 0 && ++$returnedItems >= $this->limit) {
                break;
            }

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
     * Sets the offset of the list.
     * The offset is the number of items that are skipped before the first item is returned.
     * @param int $offset
     * @return $this
     */
    public function setOffset(int $offset): static
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Sets the limit of the list.
     * The limit is the maximum number of items that are returned.
     * @param int $limit
     * @return $this
     */
    public function setLimit(int $limit): static
    {
        $this->limit = $limit;
        return $this;
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
     * @param Uuid ...$ids
     * @return iterable<T>
     */
    protected function fetchItems(Uuid ...$ids): iterable
    {
        return ($this->itemStreamFactory)(...$ids);
    }
}
