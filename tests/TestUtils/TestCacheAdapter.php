<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\TestUtils;


use Hawk\AuthClient\Cache\CacheAdapterInterface;
use Hawk\AuthClient\Cache\Util\RememberingCacheTrait;

class TestCacheAdapter implements CacheAdapterInterface
{
    use RememberingCacheTrait;

    protected array $data;

    public function __construct(array|null $initialData = null)
    {
        $this->data = $initialData ?? [];
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function get(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function set(string $key, mixed $value, ?int $ttl = null): void
    {
        if ($ttl === 0) {
            return;
        }
        $this->data[$key] = $value;
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function delete(string $key): void
    {
        unset($this->data[$key]);
    }

    public function getData(): array
    {
        return $this->data;
    }
}
