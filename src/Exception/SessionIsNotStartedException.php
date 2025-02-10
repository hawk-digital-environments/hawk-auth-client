<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Exception;


class SessionIsNotStartedException extends \RuntimeException implements AuthClientExceptionInterface
{
    public function __construct()
    {
        parent::__construct('Session is not started, please start the session before using this adapter');
    }
}
