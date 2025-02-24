<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Resources;


use Hawk\AuthClient\Cache\CacheAdapterInterface;
use Hawk\AuthClient\Cache\Util\AbstractEntityCache;
use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Resources\Value\ResourceConstraints;
use Hawk\AuthClient\Util\Uuid;
use Stringable;

/**
 * @extends AbstractEntityCache<\Hawk\AuthClient\Resources\Value\Resource>
 */
class ResourceCache extends AbstractEntityCache
{
    public const string CACHE_KEY = 'keycloak.resources';

    protected KeycloakApiClient $api;
    protected ResourceFactory $resourceFactory;

    protected array $nameToIdMap = [];

    public function __construct(
        CacheAdapterInterface $cache,
        ResourceFactory       $resourceFactory,
        KeycloakApiClient     $api
    )
    {
        parent::__construct($cache);
        $this->api = $api;
        $this->cache = $cache;
        $this->resourceFactory = $resourceFactory;
    }

    /**
     * Reliably resolves the resource ID from a resource name or ID.
     * If the resource name is not found, it will return null.
     *
     * @param string|Stringable $identifier
     * @return Uuid|null
     */
    public function getResourceId(string|\Stringable $identifier): Uuid|null
    {
        if (array_key_exists($identifier, $this->nameToIdMap)) {
            return $this->nameToIdMap[$identifier];
        }

        if (Uuid::isValid($identifier)) {
            return new Uuid($identifier);
        }

        return $this->nameToIdMap[$identifier] = $this->cache->remember(
            $this->getResourceIdMapCacheKey($identifier),
            valueGenerator: fn() => $this->api->fetchResourceByName($identifier)?->getId(),
            valueToCache: fn($id) => $id instanceof Uuid ? (string)$id : false,
            cacheToValue: fn($id) => $id === false || $id === null ? null : new Uuid($id),
            ttl: fn($resource) => $resource === null ? 60 * 60 : null
        );
    }

    /**
     * Returns a lazy stream of resource IDs that match the given constraints.
     *
     * @param ResourceConstraints|null $constraints
     * @return iterable<Uuid>
     */
    public function getResourceIdStream(ResourceConstraints|null $constraints): iterable
    {
        yield from $this->api->fetchResourceIdStream($constraints, $this->cache);
    }

    protected function getResourceIdMapCacheKey(string $resourceName): string
    {
        return self::CACHE_KEY . '.nameToIdMap.' . hash('sha256', $resourceName);
    }

    /**
     * @inheritDoc
     */
    #[\Override] protected function getCacheKey(Uuid $id): string
    {
        return self::CACHE_KEY . '.' . $id;
    }

    /**
     * @inheritDoc
     */
    #[\Override] protected function fetchItems(Uuid ...$ids): iterable
    {
        return $this->api->fetchResourcesByIds(...$ids);
    }

    /**
     * @inheritDoc
     */
    #[\Override] protected function unserializeObject(Uuid $id, array $data): object
    {
        return $this->resourceFactory->makeResourceFromCacheData($data);
    }

    /**
     * @inheritDoc
     */
    #[\Override] protected function serializeObject(Uuid $id, object $item): array
    {
        // Passive learning of resource name to ID mapping
        $this->nameToIdMap[$item->getName()] = $item->getId();
        $this->cache->set($this->getResourceIdMapCacheKey($item->getName()), $item->getId());

        return $item->jsonSerialize();
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function flushResolved(): void
    {
        parent::flushResolved();
        $this->nameToIdMap = [];
    }
}
