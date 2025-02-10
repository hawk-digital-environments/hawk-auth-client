<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Exception;


class InvalidOAuthStateException extends \RuntimeException implements AuthClientExceptionInterface
{
    public function __construct()
    {
        parent::__construct(
            'An invalid state was received in the OAuth callback. This may be a sign of a CSRF attack.'
        );
    }
}
