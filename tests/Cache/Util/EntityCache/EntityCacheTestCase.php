<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Cache\Util\EntityCache;


use Hawk\AuthClient\Cache\CacheAdapterInterface;
use Hawk\AuthClient\Cache\NullCacheAdapter;
use Hawk\AuthClient\Cache\Util\AbstractEntityCache;
use Hawk\AuthClient\Util\Uuid;
use PHPUnit\Framework\TestCase;

abstract class EntityCacheTestCase extends TestCase
{
    protected function createSut(
        CacheAdapterInterface|null $cache = null,
        callable|null              $getCacheKey = null,
        callable|null              $fetchItems = null,
        callable|null              $unserialize = null,
        callable|null              $serialize = null,
        callable|null              $getCacheTtl = null
    )
    {
        $cache ??= new NullCacheAdapter();
        $getCacheKey ??= fn(Uuid $id) => (string)$id;
        $fetchItems ??= fn(Uuid ...$ids) => [];
        $unserialize ??= fn(Uuid $id, array $data) => unserialize($data['v']);
        $serialize ??= fn($id, $data) => ['v' => serialize($data)];

        return new class($cache, $getCacheKey, $fetchItems, $unserialize, $serialize, $getCacheTtl) extends AbstractEntityCache {
            /**
             * @var callable
             */
            private $getCacheKey;
            /**
             * @var callable
             */
            private $fetchItems;
            /**
             * @var callable
             */
            private $unserialize;
            /**
             * @var callable
             */
            private $serialize;
            /**
             * @var callable|null
             */
            private $getCacheTtl;

            public function __construct(
                CacheAdapterInterface $cache,
                callable              $getCacheKey,
                callable              $fetchItems,
                callable              $unserialize,
                callable              $serialize,
                callable|null         $getCacheTtl
            )
            {
                parent::__construct($cache);
                $this->cache = $cache;
                $this->getCacheKey = $getCacheKey;
                $this->fetchItems = $fetchItems;
                $this->unserialize = $unserialize;
                $this->serialize = $serialize;
                $this->getCacheTtl = $getCacheTtl;
            }

            protected function getCacheTtl(Uuid $id): int|null
            {
                if ($this->getCacheTtl === null) {
                    return parent::getCacheTtl($id);
                }
                return ($this->getCacheTtl)($id);
            }

            protected function getCacheKey(Uuid $id): string
            {
                return ($this->getCacheKey)($id);
            }

            protected function fetchItems(Uuid ...$ids): iterable
            {
                return ($this->fetchItems)(...$ids);
            }

            protected function unserializeObject(Uuid $id, array $data): object
            {
                return ($this->unserialize)($id, $data);
            }

            protected function serializeObject(Uuid $id, object $item): array
            {
                return ($this->serialize)($id, $item);
            }
        };
    }
}
