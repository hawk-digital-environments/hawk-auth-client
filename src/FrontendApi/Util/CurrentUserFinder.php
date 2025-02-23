<?php
declare(strict_types=1);


namespace Hawk\AuthClient\FrontendApi\Util;


use Hawk\AuthClient\Auth\KeycloakProvider;
use Hawk\AuthClient\Users\UserStorage;
use Hawk\AuthClient\Users\Value\User;
use League\OAuth2\Client\Token\AccessToken;

class CurrentUserFinder
{
    protected UserStorage $userStorage;
    protected KeycloakProvider $keycloakProvider;

    public function __construct(
        UserStorage      $userStorage,
        KeycloakProvider $keycloakProvider
    )
    {
        $this->userStorage = $userStorage;
        $this->keycloakProvider = $keycloakProvider;
    }

    /**
     * Tries to find the user currently authenticated by the "Bearer" token in the given request.
     * If the token is not present or invalid, a 401 Unauthorized response is returned.
     * @param Request $request
     * @param ResponseFactory $responseFactory
     * @return User|mixed The user object if found, or a 401 Unauthorized response otherwise.
     */
    public function findUser(Request $request, ResponseFactory $responseFactory): mixed
    {
        $token = $request->getBearerToken();

        if ($token === null) {
            return $responseFactory->buildUnauthorized();
        }

        try {
            $token = new AccessToken([
                'access_token' => $token,
            ]);

            $user = $this->userStorage->getOneByToken(
                $token,
                fn() => $this->keycloakProvider->getResourceOwner($token)
            );
            return $user ?? $responseFactory->buildUnauthorized();
        } catch (\Throwable $e) {
            return $responseFactory->buildUnauthorized($e->getMessage());
        }
    }
}
