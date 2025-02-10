<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Cache;


use Hawk\AuthClient\Cache\Util\RememberingCacheTrait;
use Hawk\AuthClient\Clock\SystemClock;
use Hawk\AuthClient\Exception\FileCacheCanNotCreateStorageDirException;
use Hawk\AuthClient\Exception\FileCacheCanNotWriteFileException;
use Psr\Clock\ClockInterface;

class FileCacheAdapter implements CacheAdapterInterface
{
    protected const int DEFAULT_MAX_FILE_AGE = 60 * 60 * 24; // 1 day
    protected const int GC_INTERVAL = 60 * 60 * 6; // 6 hours
    public const string GC_TIMESTAMP_FILE = 'gc-timestamp';

    use RememberingCacheTrait;

    protected const int TIMESTAMP_LENGTH = 12;

    protected string $basePath;
    protected ClockInterface $clock;
    protected int $maxFileAge;
    protected string $infiniteExpiresAt;
    protected bool $gcCheckDone = false;

    /**
     * @param string|null $basePath A directory where the cache files are stored. If not set, the system's temporary directory is used.
     * @param ClockInterface|null $clock A clock implementation to get the current time. If not set, the system clock is used.
     */
    public function __construct(string|null $basePath = null, ClockInterface|null $clock = null, int|null $maxFileAge = null)
    {
        $this->basePath = $basePath ?? (sys_get_temp_dir() . '/hawk-auth-client-cache');
        $this->clock = $clock ?? new SystemClock();
        $this->maxFileAge = $maxFileAge ?? self::DEFAULT_MAX_FILE_AGE;
        $this->infiniteExpiresAt = str_pad('-88', self::TIMESTAMP_LENGTH, '0', STR_PAD_LEFT);
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function get(string $key): mixed
    {
        $filename = $this->getFilename($key);
        if (!is_file($filename) || !is_readable($filename)) {
            return null;
        }

        $data = file_get_contents($filename);
        if ($data === false) {
            // @codeCoverageIgnoreStart
            // This is a rare case which is a hassle to test
            return null;
            // @codeCoverageIgnoreEnd
        }

        $expiresAt = substr($data, 0, self::TIMESTAMP_LENGTH);
        $data = substr($data, self::TIMESTAMP_LENGTH);

        if (
            $expiresAt !== $this->infiniteExpiresAt
            && (int)$expiresAt < $this->clock->now()->getTimestamp()
        ) {
            unlink($filename);
            return null;
        }

        /** @noinspection UnserializeExploitsInspection */
        return unserialize($data);
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function set(string $key, mixed $value, int|null $ttl = null): void
    {
        $filename = $this->getFilename($key);
        $this->prepareStorageDirectory($filename);

        if ($ttl === null) {
            $expiresAt = $this->infiniteExpiresAt;
        } else if ($ttl < 0) {
            // TTL is negative, so we don't store the value
            return;
        } else {
            $expiresAt = $this->clock->now()->getTimestamp() + $ttl;
        }
        $expiresAt = str_pad((string)$expiresAt, self::TIMESTAMP_LENGTH, '0', STR_PAD_LEFT);

        if (!file_put_contents($filename, $expiresAt . serialize($value))) {
            // @codeCoverageIgnoreStart
            // This is a rare case which is a hassle to test
            throw new FileCacheCanNotWriteFileException($key, $filename);
            // @codeCoverageIgnoreEnd
        }

        $this->garbageCollect();
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function delete(string $key): void
    {
        $filename = $this->getFilename($key);
        if (!is_file($filename)) {
            return;
        }
        unlink($filename);
        $this->garbageCollect();
    }

    protected function getFilename(string $key): string
    {
        $hashedKey = hash('sha256', $key);
        $storagePath = $this->basePath . '/' . $hashedKey[0] . '/' . $hashedKey[1];
        return $storagePath . '/' . $hashedKey;
    }

    protected function prepareStorageDirectory(string $filename): void
    {
        $storagePath = dirname($filename);
        if (!is_dir($storagePath) && !@mkdir($storagePath, 0777, true) && !is_dir($storagePath)) {
            throw new FileCacheCanNotCreateStorageDirException($storagePath);
        }
    }

    /**
     * Automatically removes all files that are older than the max file age.
     * This method is called every time a cache entry is begin set,
     * however the garbage collection is only performed once every GC_INTERVAL seconds
     * and at most once per request.
     *
     * @return void
     */
    protected function garbageCollect(): void
    {
        if ($this->gcCheckDone) {
            return;
        }
        $this->gcCheckDone = true;

        $gcTimestampFile = $this->basePath . '/' . self::GC_TIMESTAMP_FILE;
        $nowTs = $this->clock->now()->getTimestamp();
        $lastGcTs = 0;
        if (is_file($gcTimestampFile)) {
            $lastGcTs = (int)file_get_contents($gcTimestampFile);
        }

        if ($lastGcTs + self::GC_INTERVAL > $nowTs) {
            return;
        }

        file_put_contents($gcTimestampFile, (string)$nowTs);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->basePath, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            if ($file->isFile() && $file->getMTime() + $this->maxFileAge < $nowTs) {
                unlink($file->getPathname());
            }
        }
    }
}
