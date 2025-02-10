<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Cache;


use DateTimeImmutable;
use Hawk\AuthClient\Cache\FileCacheAdapter;
use Hawk\AuthClient\Clock\SystemClock;
use Hawk\AuthClient\Exception\FileCacheCanNotCreateStorageDirException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(FileCacheAdapter::class)]
#[CoversClass(FileCacheCanNotCreateStorageDirException::class)]
class FileCacheAdapterTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/hawk-test-' . uniqid('', true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testItReturnsNullWhenGettingNonExistentKey(): void
    {
        $sut = new FileCacheAdapter($this->tempDir);
        $this->assertNull($sut->get('non-existent-key'));
    }

    public function testItCanSetAndGet(): void
    {
        $sut = new FileCacheAdapter($this->tempDir);
        $testData = ['int' => 42, 'string' => 'test', 'object' => new \stdClass()];

        $sut->set('test-key', $testData, 3600);
        $result = $sut->get('test-key');

        $this->assertEquals($testData, $result);
        $this->assertSame(42, $result['int']); // Test exact type preservation
    }

    public function testItCanHandleAnExpiredEntry(): void
    {
        $sut = new FileCacheAdapter($this->tempDir, new SystemClock(new DateTimeImmutable('2021-01-01 00:00:00')));
        $sut->set('expired-key', 'data', 1);

        $sut = new FileCacheAdapter($this->tempDir, new SystemClock(new DateTimeImmutable('2021-01-01 00:00:02')));
        $this->assertNull($sut->get('expired-key'));
    }

    public function testItCanDelete(): void
    {
        $sut = new FileCacheAdapter($this->tempDir);
        $sut->set('delete-key', 'data');
        $sut->delete('delete-key');

        $this->assertNull($sut->get('delete-key'));
    }

    public function testItCanDeleteNotExistingFiles(): void
    {
        $sut = new FileCacheAdapter($this->tempDir);
        $sut->delete('non-existing');
        $this->expectNotToPerformAssertions();
    }

    public function testItCanCollectGarbage(): void
    {
        $nowTs = time();
        $gcTsFile = $this->tempDir . '/' . FileCacheAdapter::GC_TIMESTAMP_FILE;

        // Use a timestamp in the past, so everything is expired
        $sut = new FileCacheAdapter($this->tempDir, new SystemClock(new \DateTimeImmutable('@' . $nowTs)));

        // Create multiple test files
        for ($i = 0; $i < 150; $i++) {
            $sut->set("gc-key-$i", "value-$i");
        }

        $this->assertStringEqualsFile($gcTsFile, (string)$nowTs);

        // With a new sut, but the same system time, there should be nothing to be garbage collected
        $sut = new FileCacheAdapter($this->tempDir, new SystemClock(new \DateTimeImmutable('@' . $nowTs)));
        $sut->set('gc-trigger', 'value');
        $this->assertGreaterThanOrEqual(150, count(glob($this->tempDir . '/*/*/*', GLOB_NOSORT)), 'There should not have been any garbage collection, yet');
        $this->assertStringEqualsFile($gcTsFile, (string)$nowTs);

        // With a new sut that is already in the future (tomorrow, so everything should be invalidated)
        // the garbage collector should trigger, but because of the extended file age, nothing should be deleted
        $tomorrowTs = $nowTs + 60 * 60 * 24;
        $sut = new FileCacheAdapter(
            $this->tempDir,
            new SystemClock(new \DateTimeImmutable('@' . $tomorrowTs)),
            60 * 60 * 24 * 2
        );
        $sut->set('gc-trigger', 'value');
        $this->assertGreaterThanOrEqual(150, count(glob($this->tempDir . '/*/*/*', GLOB_NOSORT)), 'There should not have been any garbage collection, yet');
        $this->assertStringEqualsFile($gcTsFile, (string)$tomorrowTs);

        // Now, with the same but the default file lifetime, the garbage collector should trigger and clean up
        $dayAfterTomorrowTs = $tomorrowTs + 60 * 60 * 24; // Because the previous test set the timestamp in the future, we need to match it here by going even further
        $sut = new FileCacheAdapter(
            $this->tempDir,
            new SystemClock(new \DateTimeImmutable('@' . $dayAfterTomorrowTs)),
        );
        $sut->set('gc-trigger', 'value');
        $this->assertLessThan(150, count(glob($this->tempDir . '/*/*/*', GLOB_NOSORT)), 'GC should clean some files');
    }

    public static function provideTestItCanPreserveTheDataTypesData(): iterable
    {
        yield 'int' => [42];
        yield 'string' => ['hello'];
        yield 'array' => [[1, 2, 3]];
        yield 'float' => [3.14];
        yield 'bool' => [true];
        yield 'null' => [null];
    }

    #[DataProvider('provideTestItCanPreserveTheDataTypesData')]
    public function testItCanPreserveTheDataTypes(mixed $value): void
    {
        $sut = new FileCacheAdapter($this->tempDir);
        $sut->set('key', $value);
        $this->assertSame($value, $sut->get('key'));
    }

    public function testItCanSetAndGetItemsWithInfiniteTtl(): void
    {
        $sut = new FileCacheAdapter($this->tempDir);
        $sut->set('infinite-key', 'data');
        $this->assertEquals('data', $sut->get('infinite-key'));
    }

    public function testItCanSetWithNegativeTtl(): void
    {
        $sut = new FileCacheAdapter($this->tempDir);
        $sut->set('key', 'data', -1);
        $this->assertNull($sut->get('key'));
    }

    public function testItFailsIfStorageDirectoryCanNotBeCreated(): void
    {
        $this->expectException(FileCacheCanNotCreateStorageDirException::class);
        $sut = new FileCacheAdapter('/dev/subdir');
        $sut->set('key', 'value');
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            if ($fileinfo->isDir()) {
                rmdir($fileinfo->getRealPath());
            } else {
                unlink($fileinfo->getRealPath());
            }
        }

        rmdir($dir);
    }
}
