<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Exception;


class ProfileUpdateFailedException extends \DomainException implements AuthClientExceptionInterface
{
    public function __construct(string $message, \Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
