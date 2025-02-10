<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Exception;


class InvalidUuidException extends \InvalidArgumentException implements AuthClientExceptionInterface
{
    public function __construct(string $uuid)
    {
        parent::__construct(sprintf('The given value does not look like a uuid: "%s"', $uuid));
    }
}
