<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Exception;


class UserToGuardNotFoundException extends \InvalidArgumentException implements AuthClientExceptionInterface
{
    public function __construct(string $userId)
    {
        parent::__construct(sprintf(
            'Failed to create a guard, because the given user id "%s" does not exist.',
            $userId
        ));
    }
}
