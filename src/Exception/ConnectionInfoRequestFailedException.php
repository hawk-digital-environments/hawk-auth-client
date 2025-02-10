<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Exception;


class ConnectionInfoRequestFailedException extends AbstractRequiredRequestFailedException
{
    public function __construct(\Throwable $previous)
    {
        parent::__construct(
            sprintf(
                'Failed to request the connection information of the backend server: %s. You need the "hawk-client" role for the service account user.',
                $previous->getMessage(),
            ), 0, $previous
        );
    }
}
