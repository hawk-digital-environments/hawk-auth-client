<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Cache\Util;


use Generator;
use Hawk\AuthClient\Cache\CacheAdapterInterface;

/**
 * @template T of object
 * @internal This class is not part of the public API and may change at any time.
 */
abstract class AbstractEntityCache
{
    protected CacheAdapterInterface $cache;
    protected array $resolved = [];

    public function __construct(CacheAdapterInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Returns the items for the given ids. If an item is not found locally, it will be fetched.
     * Ids that do not resolve to an item will be ignored.
     *
     * @param string ...$ids
     * @return Generator<T>
     */
    public function getAllByIds(string ...$ids): iterable
    {
        $knownItems = [];
        $missingItemIds = [];

        foreach ($ids as $id) {
            $item = $this->getOne($id, false);
            if ($item === null) {
                $missingItemIds[] = $id;
            } else {
                $knownItems[$id] = $item;
            }
        }

        if (!empty($missingItemIds)) {
            foreach ($this->fetchItems(...$missingItemIds) as $id => $item) {
                $this->save($id, $item);
                $knownItems[$id] = $item;
            }
        }

        foreach ($ids as $id) {
            if (isset($knownItems[$id])) {
                yield $knownItems[$id];
            }
        }
    }

    /**
     * Returns the item for the given id. If the item is not found locally, it will be fetched.
     * If the item does not exist, null will be returned.
     *
     * @param string $id The id of the item
     * @param bool $fetchMissing Whether to fetch the item if it is not found locally,
     *                           true by default (meaning the item will be fetched if it is not found),
     *                           false if the item should not be fetched if it is not found.
     * @return T|null
     */
    public function getOne(string $id, bool $fetchMissing = true): object|null
    {
        if (array_key_exists($id, $this->resolved)) {
            return $this->resolved[$id];
        }

        // @memo to self: no, you can NOT use CacheAdapterInterface::remember here, because of $fetchMissing!
        $cacheKey = $this->getCacheKey($id);
        $cached = $this->cache->get($cacheKey);
        if ($cached === null) {
            if (!$fetchMissing) {
                return null;
            }

            $items = iterator_to_array($this->fetchItems($id), false);
            if (empty($items)) {
                // Item not found -> do not check again...
                $this->resolved[$id] = null;
                $this->cache->set($cacheKey, false, $this->getCacheTtl($id));
                return null;
            }

            // Persist fetched item
            $item = $items[0];
            $this->save($id, $item);
            return $item;
        }

        // False means we checked for the item and it does not exist
        if ($cached === false) {
            return null;
        }

        return $this->resolved[$id] = $this->unserializeObject($id, $cached);
    }

    /**
     * Saves the item in the cache and in the local resolved items.
     * @param string $id
     * @param T $item
     */
    public function save(string $id, object $item): void
    {
        $this->resolved[$id] = $item;
        $this->cache->set(
            $this->getCacheKey($id),
            $this->serializeObject($id, $item),
            $this->getCacheTtl($id)
        );
    }

    /**
     * Removes the item from the cache and the local resolved items.
     * @param string $id
     * @return void
     */
    public function remove(string $id): void
    {
        unset($this->resolved[$id]);
        $this->cache->delete($this->getCacheKey($id));
    }

    /**
     * Flushes the resolved items. This will not remove the items from the cache, but will re-pull them the next time they are requested.
     * @return void
     */
    public function flushResolved(): void
    {
        $this->resolved = [];
    }

    /**
     * Returns the cache ttl for the given id.
     * @param string $id The id of the item to get the ttl for
     * @return int|null The ttl in seconds, or null if the item should be cached indefinitely
     */
    protected function getCacheTtl(string $id): int|null
    {
        return null;
    }

    /**
     * Used to calculate the cache key for the given id. The key should be unique for each id.
     * @param string $id
     * @return string
     */
    abstract protected function getCacheKey(string $id): string;

    /**
     * Fetches the items for the given ids. The keys of the returned array should be the ids of the items.
     * The cache does not care how the items are resolved, it iterates them all and saves them.
     *
     * @param string ...$ids
     * @return iterable<string, T>
     */
    abstract protected function fetchItems(string ...$ids): iterable;

    /**
     * Executed when an item has been stored in cache and must now be unserialized.
     * @param string $id The id of the item
     * @param array $data The serialized data
     * @return T
     */
    abstract protected function unserializeObject(string $id, array $data): object;

    /**
     * Executed when an item must be stored in cache and must now be serialized.
     * @param string $id The id of the item
     * @param T $item The item to serialize
     * @return array
     */
    abstract protected function serializeObject(string $id, object $item): array;
}
