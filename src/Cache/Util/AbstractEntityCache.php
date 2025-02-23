<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Cache\Util;


use Generator;
use Hawk\AuthClient\Cache\CacheAdapterInterface;
use Hawk\AuthClient\Util\Uuid;

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
     * @param Uuid ...$ids
     * @return Generator<T>
     */
    public function getAllByIds(Uuid ...$ids): iterable
    {
        $knownItems = [];
        $missingItemIds = [];

        foreach ($ids as $id) {
            $idString = (string)$id;
            $item = $this->getOne($id, false);
            if ($item === null) {
                $missingItemIds[] = $id;
            } else {
                $knownItems[$idString] = $item;
            }
        }

        if (!empty($missingItemIds)) {
            foreach ($this->fetchItems(...$missingItemIds) as $id => $item) {
                $idString = (string)$id;
                $this->save($id, $item);
                $knownItems[$idString] = $item;
            }
        }

        foreach ($ids as $id) {
            $idString = (string)$id;
            if (isset($knownItems[$idString])) {
                yield $knownItems[$idString];
            }
        }
    }

    /**
     * Returns the item for the given id. If the item is not found locally, it will be fetched.
     * If the item does not exist, null will be returned.
     *
     * @param Uuid $id The id of the item
     * @param bool $fetchMissing Whether to fetch the item if it is not found locally,
     *                           true by default (meaning the item will be fetched if it is not found),
     *                           false if the item should not be fetched if it is not found.
     * @return T|null
     */
    public function getOne(Uuid $id, bool $fetchMissing = true): object|null
    {
        $idString = (string)$id;
        if (array_key_exists($idString, $this->resolved)) {
            return $this->resolved[$idString];
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
                $this->resolved[$idString] = null;
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

        return $this->resolved[$idString] = $this->unserializeObject($id, $cached);
    }

    /**
     * Saves the item in the cache and in the local resolved items.
     * @param Uuid $id
     * @param object $item
     */
    public function save(Uuid $id, object $item): void
    {
        $this->resolved[(string)$id] = $item;
        $this->cache->set(
            $this->getCacheKey($id),
            $this->serializeObject($id, $item),
            $this->getCacheTtl($id)
        );
    }

    /**
     * Removes the item from the cache and the local resolved items.
     * @param Uuid $id
     * @return void
     */
    public function remove(Uuid $id): void
    {
        unset($this->resolved[(string)$id]);
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
     * @param Uuid $id The id of the item to get the ttl for
     * @return int|null The ttl in seconds, or null if the item should be cached indefinitely
     */
    protected function getCacheTtl(Uuid $id): int|null
    {
        return null;
    }

    /**
     * Used to calculate the cache key for the given id. The key should be unique for each id.
     * @param Uuid $id
     * @return string
     */
    abstract protected function getCacheKey(Uuid $id): string;

    /**
     * Fetches the items for the given ids. The keys of the returned array should be the ids of the items.
     * The cache does not care how the items are resolved, it iterates them all and saves them.
     *
     * @param Uuid ...$ids
     * @return iterable<Uuid, T>
     */
    abstract protected function fetchItems(Uuid ...$ids): iterable;

    /**
     * Executed when an item has been stored in cache and must now be unserialized.
     * @param Uuid $id The id of the item
     * @param array $data The serialized data
     * @return T
     */
    abstract protected function unserializeObject(Uuid $id, array $data): object;

    /**
     * Executed when an item must be stored in cache and must now be serialized.
     * @param Uuid $id The id of the item
     * @param T $item The item to serialize
     * @return array
     */
    abstract protected function serializeObject(Uuid $id, object $item): array;
}
