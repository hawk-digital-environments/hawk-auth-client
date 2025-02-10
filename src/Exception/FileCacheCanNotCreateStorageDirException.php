<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Exception;


class FileCacheCanNotCreateStorageDirException extends \RuntimeException implements AuthClientExceptionInterface
{
    public function __construct(string $storageDir)
    {
        parent::__construct(
            sprintf(
                'The storage directory "%s" can not be created. Please check the permissions of the parent directory.',
                $storageDir
            )
        );
    }
}
