<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Util;


use Hawk\AuthClient\Tests\TestUtils\DummyUuid;
use Hawk\AuthClient\Util\AbstractChunkedList;
use Hawk\AuthClient\Util\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractChunkedList::class)]
class AbstractChunkedListTest extends TestCase
{
    protected AbstractChunkedList $sut;
    protected array $ids;

    public function setUp(): void
    {
        $this->ids = array_map(fn(int $i) => new DummyUuid($i), range(1, 687));

        $this->sut = new class(fn() => $this->ids, fn() => null) extends AbstractChunkedList {
            public function setItemStreamFactory(callable $itemStreamFactory): void
            {
                $this->itemStreamFactory = $itemStreamFactory;
            }

            #[\Override] public function getChunkSize(): int
            {
                return parent::getChunkSize();
            }
        };
    }

    public function testItCanBeIterated(): void
    {
        $this->assertIsIterable($this->sut);
        $count = 0;

        $this->sut->setItemStreamFactory(function (Uuid ...$ids) use (&$count): iterable {
            $expectedIds = array_slice(
                $this->ids,
                $count++ * $this->sut->getChunkSize(),
                $this->sut->getChunkSize()
            );
            $this->assertEquals($expectedIds, $ids);
            return $ids;
        });

        $expected = $this->ids;
        $given = iterator_to_array($this->sut, false);

        $this->assertEquals(
            round(count($this->ids) / $this->sut->getChunkSize()),
            $count
        );
        $this->assertEquals($expected, $given);
    }

    public function testItCanHaveLimitAndOffsetSet(): void
    {
        $offset = $this->sut->getChunkSize() * 2;
        $limit = $this->sut->getChunkSize() * 3 + 6;
        $count = 0;
        $this->sut->setItemStreamFactory(function (Uuid ...$ids) use (&$count, $offset): iterable {
            $expectedIds = array_slice(
                $this->ids,
                $count++ * $this->sut->getChunkSize() + $offset,

                // Last chunk will should only request 6 items
                $count === 4 ? 6 : $this->sut->getChunkSize()
            );
            $this->assertEquals($expectedIds, $ids);
            return $ids;
        });

        $this->sut
            ->setOffset($offset)
            ->setLimit($limit);

        $expected = array_slice($this->ids, $offset, $limit);
        $given = iterator_to_array($this->sut, false);
        $this->assertEquals($expected, $given);
        $this->assertEquals(4, $count);
    }
}
