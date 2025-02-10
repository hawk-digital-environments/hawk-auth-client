<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Exception;


class NoResourceOwnerAssignedException extends \DomainException implements AuthClientExceptionInterface
{
    public function __construct()
    {
        parent::__construct('You are creating a new resource, and do not have an owner assigned to it.');
    }
}
