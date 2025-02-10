<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Exception;


class MissingHawkUserClaimException extends AbstractInvalidUserDataException
{
    public function __construct(string $userId)
    {
        parent::__construct(
            sprintf(
                'The user with ID "%s" could not be loaded, because the server response misses the "hawk" claim. The claim must be present to load a user. Did you add the "hawk-client" to the Client scopes in Keycloak using the "Default" setting?',
                $userId
            )
        );
    }
}
