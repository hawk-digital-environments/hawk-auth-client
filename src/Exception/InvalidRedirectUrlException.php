<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Exception;


class InvalidRedirectUrlException extends \InvalidArgumentException implements AuthClientExceptionInterface
{
    public function __construct(string $givenUrl)
    {
        parent::__construct(
            sprintf(
                'The given redirect URL "%s" is invalid. It must be a valid URL.',
                $givenUrl
            )
        );
    }
}
