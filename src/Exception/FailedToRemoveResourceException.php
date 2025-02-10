<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Exception;


use Hawk\AuthClient\Resources\Value\Resource;

class FailedToRemoveResourceException extends \DomainException implements AuthClientExceptionInterface
{
    public function __construct(string $message, Resource $resource, \Throwable|null $previous = null)
    {
        parent::__construct(
            sprintf(
                'Failed to remove the resource with name "%s" because: %s',
                $resource->getName(),
                $message
            ),
            previous: $previous
        );
    }
}
