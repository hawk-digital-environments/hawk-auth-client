<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Exception;


class NoResourceIdAssignedException extends \DomainException implements AuthClientExceptionInterface
{
    public function __construct()
    {
        parent::__construct('You are creating a new resource, it does not have an ID yet');
    }
}
