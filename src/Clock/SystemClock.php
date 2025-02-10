<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Clock;


use DateTimeImmutable;
use Psr\Clock\ClockInterface;

class SystemClock implements ClockInterface
{
    private ?DateTimeImmutable $now;

    public function __construct(\DateTimeImmutable|null $now = null)
    {
        $this->now = $now;
    }

    /**
     * @inheritDoc
     */
    public function now(): DateTimeImmutable
    {
        return $this->now ?? new DateTimeImmutable();
    }
}
