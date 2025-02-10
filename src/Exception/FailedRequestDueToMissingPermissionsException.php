<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Exception;


class FailedRequestDueToMissingPermissionsException extends AbstractRequiredRequestFailedException
{
    public function __construct(string $requiredPermission, string|null $message = null, \Throwable|null $previous = null)
    {
        parent::__construct(
            sprintf(
                'The request failed because the required client permission(service-account-role) "%s" is missing.%s',
                $requiredPermission,
                $message ? ' ' . $message : ''
            ),
            previous: $previous
        );
    }
}
