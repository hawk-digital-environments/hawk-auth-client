<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Exception;


class ChunkedQueryInfiniteLoopException extends \RuntimeException implements AuthClientExceptionInterface
{
    public function __construct(int $maxIterations)
    {
        parent::__construct(
            sprintf(
                'While executing a chunked query, the maximum number of requests (%d) has been reached. Are you sure you are not creating an infinite loop?',
                $maxIterations
            )
        );
    }
}
