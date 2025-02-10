<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Exception;


class ProfileDefinitionFailedException extends AbstractRequiredRequestFailedException
{
    public function __construct(
        string $error,
        \Throwable $previous
    )
    {
        parent::__construct(
            'Profile definition failed: ' . $error,
            0,
            $previous
        );
    }
}
