<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Auth;


use Hawk\AuthClient\Permissions\Guard;
use Hawk\AuthClient\Permissions\GuardFactory;
use Hawk\AuthClient\Users\UserStorage;
use Hawk\AuthClient\Users\Value\User;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Psr\Log\LoggerInterface;

class StatelessAuth
{
    private UserStorage $userStorage;
    private GuardFactory $guardFactory;
    private AccessTokenInterface|null $token = null;
    private User|null $user = null;
    private KeycloakProvider $provider;
    private LoggerInterface $logger;

    public function __construct(
        UserStorage      $userStorage,
        GuardFactory     $guardFactory,
        KeycloakProvider $provider,
        LoggerInterface  $logger
    )
    {
        $this->userStorage = $userStorage;
        $this->guardFactory = $guardFactory;
        $this->provider = $provider;
        $this->logger = $logger;
    }

    /**
     * Authenticates the user based on the given token.
     * If the token is invalid or the user cannot be authenticated, the onUnauthorized callback is called.
     * If the user is authenticated, the onAuthorized callback is called.
     *
     * @param string|AccessTokenInterface $token The token to authenticate the user with.
     * @param callable|null $onUnauthorized The callback to call if the user cannot be authenticated.
     *                                      Might return a value that is returned by the authenticate method.
     *
     * @param callable|null $onAuthorized The callback to call if the user is authenticated.
     *                                    Might return a value that is returned by the authenticate method.
     * @return mixed Depending on the hooks and state returns either the result of the executed hook or null.
     */
    public function authenticate(
        string|AccessTokenInterface $token,
        callable|null               $onUnauthorized = null,
        callable|null               $onAuthorized = null
    ): mixed
    {
        if (empty($token)) {
            if ($onUnauthorized) {
                return $onUnauthorized();
            }
            return null;
        }

        if (!$token instanceof AccessTokenInterface) {
            $token = new AccessToken([
                'access_token' => $token
            ]);
        }

        $this->token = $token;
        try {
            $this->user = $this->userStorage->getOneByToken($token, fn() => $this->provider->getResourceOwner($token));
        } catch (\Throwable $e) {
            $this->logger->info(
                '[StatelessAuth] Failed to validate token',
                [
                    'exception' => $e
                ]
            );
        }

        if ($this->user instanceof User) {
            if ($onAuthorized) {
                return $onAuthorized($token);
            }
        } else if ($onUnauthorized) {
            return $onUnauthorized();
        }

        return null;
    }

    /**
     * Returns the current token, if present.
     * @return AccessTokenInterface|null
     */
    public function getToken(): AccessTokenInterface|null
    {
        return $this->token;
    }

    /**
     * Returns the guard for the current user, if present.
     * A guard is a set of permissions that the user has, describing what the user is allowed to do and with which resources.
     * You can also check if the user is part of a specific group or has a specific role.
     *
     * @return Guard|null
     */
    public function getGuard(): Guard|null
    {
        if (!$this->user) {
            return null;
        }

        return $this->guardFactory->getOne($this->user);
    }

    /**
     * Returns the current user, if present.
     * If this method returns null, the user is not authenticated/logged in.
     * @return User|null
     */
    public function getUser(): User|null
    {
        return $this->user;
    }
}
