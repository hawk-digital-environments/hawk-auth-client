<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Exception;


use Hawk\AuthClient\Resources\Value\ResourceBuilder;

class ResourceAlreadyExistsException extends FailedToWriteResourceException
{
    public function __construct(ResourceBuilder $builder, \Throwable|null $previous = null)
    {
        parent::__construct(
            'there is already a resource with the same name',
            $builder,
            $previous
        );
    }
}
