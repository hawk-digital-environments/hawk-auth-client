<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Exception;


use Hawk\AuthClient\Resources\Value\ResourceBuilder;

class FailedToWriteResourceException extends \DomainException implements AuthClientExceptionInterface
{
    public function __construct(string $message, ResourceBuilder $resource, \Throwable|null $previous = null)
    {
        parent::__construct(
            sprintf(
                'Failed to write the resource with name "%s" because: %s',
                $resource->getName(),
                $message
            ),
            previous: $previous
        );
    }
}
