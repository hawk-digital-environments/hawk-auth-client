<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Exception;


class ResourceOwnerNotFoundException extends \DomainException implements AuthClientExceptionInterface
{
    public function __construct(string $ownerId)
    {
        parent::__construct(
            sprintf('Resource owner with ID "%s" not found in the list of available users', $ownerId)
        );
    }
}
