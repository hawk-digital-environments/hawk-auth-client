<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Exception;

/**
 * @codeCoverageIgnore not so easy to test, and does not implement a lot of logic anyway
 */
class FileCacheCanNotWriteFileException extends \RuntimeException implements AuthClientExceptionInterface
{
    public function __construct(string $key, string $filename)
    {
        parent::__construct(
            sprintf(
                'Failed to set a cache entry with key: "%s", because the file "%s" can not be written. Please check the permissions of the parent directory.',
                $key,
                $filename
            )
        );
    }
}
