<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Exception;


class InvalidPublicKeycloakUrlException extends \InvalidArgumentException implements AuthClientExceptionInterface
{
    public function __construct(string $givenUrl)
    {
        parent::__construct(
            sprintf(
                'The given public auth server URL "%s" is invalid. It must be a valid URL.',
                $givenUrl
            )
        );
    }
}
