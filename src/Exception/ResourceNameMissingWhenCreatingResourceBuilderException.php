<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Exception;


class ResourceNameMissingWhenCreatingResourceBuilderException extends \InvalidArgumentException implements AuthClientExceptionInterface
{
    public function __construct()
    {
        parent::__construct('When creating a resource builder without an existing resource, the name must be provided.');
    }
}
