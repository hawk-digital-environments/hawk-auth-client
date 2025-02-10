<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Exception;


class MissingUserIdClaimException extends AbstractInvalidUserDataException
{
    public function __construct()
    {
        parent::__construct(
            'A user could not be loaded, because the server response misses the "sub" claim. The claim must be present to load a user.',
        );
    }
}
