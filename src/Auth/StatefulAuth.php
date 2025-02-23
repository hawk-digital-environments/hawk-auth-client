<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Auth;


use Hawk\AuthClient\Exception\InvalidOAuthStateException;
use Hawk\AuthClient\Permissions\Guard;
use Hawk\AuthClient\Permissions\GuardFactory;
use Hawk\AuthClient\Request\RequestAdapterInterface;
use Hawk\AuthClient\Session\SessionAdapterInterface;
use Hawk\AuthClient\Users\UserStorage;
use Hawk\AuthClient\Users\Value\User;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Psr\Log\LoggerInterface;

class StatefulAuth
{
    private const string SESSION_KEY_OAUTH_STATE = 'oauth_state';

    private RequestAdapterInterface $requestAdapter;
    private StatefulUserTokenStorage $tokenStorage;
    private SessionAdapterInterface $sessionAdapter;
    private KeycloakProvider $provider;
    private mixed $redirectHandler;
    private UserStorage $userStorage;
    private GuardFactory $guardFactory;
    private LoggerInterface $logger;

    public function __construct(
        RequestAdapterInterface  $requestAdapter,
        StatefulUserTokenStorage $tokenStorage,
        SessionAdapterInterface  $sessionAdapter,
        KeycloakProvider         $provider,
        UserStorage              $userStorage,
        GuardFactory             $guardFactory,
        LoggerInterface          $logger
    )
    {
        $this->requestAdapter = $requestAdapter;
        $this->tokenStorage = $tokenStorage;
        $this->sessionAdapter = $sessionAdapter;
        $this->provider = $provider;
        $this->redirectHandler = static function (string $url) {
            header('Location: ' . $url);
            exit;
        };
        $this->userStorage = $userStorage;
        $this->guardFactory = $guardFactory;
        $this->logger = $logger;
    }

    /**
     * The redirect handler is responsible for redirecting the user to multiple locations.
     * By default, a native PHP header redirect is used. If you want to use a different
     * method, or use the library like a {@see https://www.php-fig.org/psr/psr-15/} server request handler,
     * you can set a custom handler that returns a redirect response here.
     *
     * The result of the handler will be returned by the calling method.
     *
     * @param callable $handler A handler that takes a string URL and either handles the redirect and exists or
     *                          returns a response that will be returned by the calling method.
     * @return void
     */
    public function setRedirectHandler(callable $handler): void
    {
        $this->redirectHandler = $handler;
    }

    /**
     * Authenticates the user. Authentication is the first step in the OAuth2 flow.
     * The method checks if the token in the session is present and valid. If it is not,
     * the $onUnauthorized hook is called. If the token is present and valid, the $onAuthorized
     * hook is called.
     *
     * @param callable|null $onUnauthorized A hook that is called when the user is not authenticated.
     *                                      This is a good place to redirect the user to the login page.
     *                                      If you really want, you can even do the {@see StatefulAuth::handleCallback()} here.
     *                                      May return a string, which is interpreted as a URL to redirect to.
     * @param callable|null $onAuthorized A hook that is called when the user is authenticated.
     *                                    May return a string, which is interpreted as a URL to redirect to.
     * @return mixed Depending on your {@see setRedirectHandler()} implementation, this will either return a response or null.
     */
    public function authenticate(
        callable|null $onUnauthorized = null,
        callable|null $onAuthorized = null
    ): mixed
    {
        $token = $this->getToken();
        if ($token === null) {
            if ($onUnauthorized !== null) {
                return $this->handleRedirectResultOfHook($onUnauthorized());
            }

            return null;
        }

        if ($token->hasExpired()) {
            $newToken = $this->refreshToken();
            if ($newToken === null) {
                if ($onUnauthorized !== null) {
                    return $this->handleRedirectResultOfHook($onUnauthorized());
                }
                return null;
            }
        }

        if ($onAuthorized !== null) {
            return $this->handleRedirectResultOfHook($onAuthorized($token));
        }

        return null;
    }

    /**
     * This method will authenticate the user if possible, otherwise it will redirect the user to the login page.
     * @param string|null $redirectUrl The URL to redirect to after the login. This is typically the URL that got submitted before the login.
     *                           For the simplest use case, you can use the current URL WITHOUT query here, but you can also
     *                           Store the URL in the session or in a cookie.
     *                           If empty/null the configured redirect URL of the client will be used.
     * @return mixed Depending on your {@see setRedirectHandler()} implementation, this will either return a response or null.
     * @see authenticate() if you need more control over the authentication process.
     */
    public function authenticateOrLogin(string|null $redirectUrl = null): mixed
    {
        $redirectUrl = empty($redirectUrl) ? $this->provider->getRedirectUrl() : $redirectUrl;
        return $this->authenticate(
            onUnauthorized: function () use ($redirectUrl) {
                return $this->handleCallback(
                    onHandled: fn() => $redirectUrl,
                    onInvalidState: fn() => $redirectUrl
                );
            }
        );
    }

    /**
     * Allows you to manually refresh the token.
     * You do not NEED to do this yourself, as the token will be refreshed automatically when needed.
     * @return AccessTokenInterface|null
     */
    public function refreshToken(): AccessTokenInterface|null
    {
        $currentToken = $this->getToken();
        if ($currentToken === null) {
            $this->logger->debug('[StatefulAuth] No token to refresh');
            return null;
        }

        try {
            $this->logger->debug('[StatefulAuth] Starting to refresh token');
            $this->tokenStorage->clear();

            $newToken = $this->provider->getAccessToken('refresh_token', [
                'refresh_token' => $currentToken->getRefreshToken()
            ]);

            if ($newToken->hasExpired()) {
                $this->logger->error('[StatefulAuth] Token is already expired after refresh, this is more than weird...');
                return null;
            }

            $this->tokenStorage->setToken($newToken);
            $this->logger->debug('[StatefulAuth] Token refreshed');
            return $newToken;
        } catch (\Throwable $e) {
            $this->logger->error('[StatefulAuth] Failed to refresh token', [
                'exception' => $e
            ]);
            return null;
        }
    }

    /**
     * Returns the current token, if present.
     * @return AccessTokenInterface|null
     */
    public function getToken(): AccessTokenInterface|null
    {
        return $this->tokenStorage->getToken();
    }

    /**
     * Redirects the user to the login page of the OAuth2 provider.
     * @return mixed Depending on your {@see setRedirectHandler()} implementation, this will either return a response or null.
     */
    public function login(): mixed
    {
        $this->logger->info('[StatefulAuth] Starting login process');
        $authorizationUrl = $this->provider->getAuthorizationUrl();
        $this->sessionAdapter->set(self::SESSION_KEY_OAUTH_STATE, $this->provider->getState());
        return ($this->redirectHandler)($authorizationUrl);
    }

    /**
     * Logs the user out. This will clear the token and redirect the user to the logout page of the OAuth2 provider.
     * @param string|null $redirectUrl If present, the user will be redirected to this URL after the logout.
     * @return mixed Depending on your {@see setRedirectHandler()} implementation, this will either return a response or null.
     */
    public function logout(string|null $redirectUrl = null): mixed
    {
        $token = $this->tokenStorage->getToken();
        if ($token !== null) {
            $this->logger->info('[StatefulAuth] Logging out');
            $this->tokenStorage->clear();
            return ($this->redirectHandler)($this->provider->getLogoutUrl($token, $redirectUrl));
        }

        // Even without a token, we can still redirect the user
        $url = $redirectUrl ?? $this->redirectUriAfterLogout ?? null;
        if (!empty($url)) {
            return ($this->redirectHandler)($url);
        }

        return null;
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
        $user = $this->getUser();
        if (!$user) {
            return null;
        }

        return $this->guardFactory->getOne($user);
    }

    /**
     * Returns the current user, if present.
     * If this method returns null, the user is not authenticated/logged in.
     * @return User|null
     */
    public function getUser(): User|null
    {
        $token = $this->getToken();
        if (!$token) {
            return null;
        }

        try {
            return $this->userStorage->getOneByToken($token, fn() => $this->provider->getResourceOwner($token));
        } catch (\Throwable $e) {
            $this->logger->info(
                '[StatefulAuth] Failed to validate token to get user',
                [
                    'exception' => $e
                ]
            );
            $this->tokenStorage->clear();
        }

        return null;
    }

    /**
     * The callback handler is responsible for receiving the OAuth2 callback and handling it.
     * This method will check if the state is valid and if the code is present. If both are true,
     * the token will be fetched and stored. If the state is invalid, the $onInvalidState hook is called.
     *
     * The code delivered to the callback is used to fetch the token from the OAuth2 provider.
     *
     * If no code is present in the query, or explicitly given, the user will be redirected to the login page.
     *
     * @param callable|null $onHandled A hook that is called when the callback is handled successfully.
     *                                 May return a string, which is interpreted as a URL to redirect to.
     * @param callable|null $onInvalidState A hook that is called when the state is invalid. This is a good place to log the user out and redirect back to the login page.
     *                                      May return a string, which is interpreted as a URL to redirect to.
     * @param string|null $code You can manually set the coded here, if omitted (default), it will be fetched from the query.
     * @return mixed Depending on your {@see setRedirectHandler()} implementation, this will either return a response or null.
     */
    public function handleCallback(
        callable|null $onHandled = null,
        callable|null $onInvalidState = null,
        string|null   $code = null
    ): mixed
    {
        $code = $code ?? $this->requestAdapter->getQueryValue('code');

        // If we don't have an authorization code then get one
        if (empty($code)) {
            $this->logger->info('[StatefulAuth] No code in callback, redirecting to login');
            return $this->login();
        }

        if ($this->sessionAdapter->get(self::SESSION_KEY_OAUTH_STATE) !== $this->requestAdapter->getQueryValue('state')) {
            $this->logger->error('[StatefulAuth] Invalid state in callback. CSRF attack?', [
                'sessionState' => $this->sessionAdapter->get(self::SESSION_KEY_OAUTH_STATE),
                'queryState' => $this->requestAdapter->getQueryValue('state')
            ]);

            $this->sessionAdapter->remove(self::SESSION_KEY_OAUTH_STATE);
            if ($onInvalidState !== null) {
                $result = $this->handleRedirectResultOfHook($onInvalidState());
                if ($result !== null) {
                    return $result;
                }
            }

            throw new InvalidOAuthStateException();
        }

        try {
            $token = $this->provider->getAccessToken('authorization_code', [
                'code' => $code
            ]);
        } catch (\Exception $e) {
            $this->logger->error('[StatefulAuth] Failed to get access token in callback.', [
                'exception' => $e
            ]);
            return $this->login();
        }

        $this->logger->debug('[StatefulAuth] Token received in callback');
        $this->tokenStorage->setToken($token);
        if ($onHandled !== null) {
            return $this->handleRedirectResultOfHook($onHandled($token));
        }

        return null;
    }

    /**
     * If a hook returns a string, it is assumed to be a URL to redirect to.
     * @param mixed $hookResult A hook to execute.
     * @return mixed Either null or the result of the given hook, if it was a non-empty string
     */
    protected function handleRedirectResultOfHook(mixed $hookResult): mixed
    {
        if (!empty($hookResult) && is_string($hookResult)) {
            return ($this->redirectHandler)($hookResult);
        }

        return null;
    }
}
