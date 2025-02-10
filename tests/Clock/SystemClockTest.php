<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Clock;


use Hawk\AuthClient\Clock\SystemClock;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SystemClock::class)]
class SystemClockTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new SystemClock();
        $this->assertInstanceOf(SystemClock::class, $sut);
    }

    public function testItReturnsTheCurrentTimestamp(): void
    {
        $sut = new SystemClock();
        // In some edge cases the test runs when the second changes, meaning that the timestamp will be different
        // To avoid this, we run the test twice, the second time we should get the same timestamp if the first try failed.
        for ($i = 0; $i < 2; $i++) {
            $now = new \DateTimeImmutable();
            if ($now->getTimestamp() === $sut->now()->getTimestamp()) {
                $this->assertEquals($now->getTimestamp(), $sut->now()->getTimestamp());
                $this->assertNotSame($now, $sut->now());
                return;
            }
        }
        $this->fail('The timestamp was different in both tries');
    }

    public function testItReturnsTheSetTimestamp(): void
    {
        $now = new \DateTimeImmutable();
        $sut = new SystemClock($now);
        $this->assertSame($now->getTimestamp(), $sut->now()->getTimestamp());
    }
}
