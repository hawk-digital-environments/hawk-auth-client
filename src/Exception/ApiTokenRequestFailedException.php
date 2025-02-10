<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Exception;


class ApiTokenRequestFailedException extends AbstractRequiredRequestFailedException
{
    public function __construct(\Throwable $previous)
    {
        parent::__construct(
            sprintf(
                'Failed to request a new API token: %s',
                $previous->getMessage()
            ), 0, $previous
        );
    }
}
