<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\TestUtils;


use Hawk\AuthClient\Util\Uuid;

/**
 * A dummy UUID class that can be used in tests.
 */
readonly class DummyUuid extends Uuid
{
    /**
     * A new uuid will always look like 'ffffffff-ffff-ffff-ffff-000000000001',
     * the last 12 characters will be a number that is padded with zeros.
     *
     * @param int|null $count An optional number if you need multiple UUIDs in your test case.
     */
    public function __construct(int|null $count = null)
    {
        parent::__construct('ffffffff-ffff-ffff-ffff-' . str_pad(
                (string)($count ?? 1),
                12,
                '0',
                STR_PAD_LEFT
            ));
    }
}
