<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Auth;


use Hawk\AuthClient\Keycloak\CacheBusterStorage;
use Hawk\AuthClient\Session\SessionAdapterInterface;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;

/**
 * @internal This class is not part of the public API and may change at any time.
 */
class StatefulUserTokenStorage
{
    protected const string TOKEN_KEY = 'auth_token';
    protected const string CACHE_BUSTER_KEY = 'auth_cache_buster';

    protected SessionAdapterInterface $session;
    protected CacheBusterStorage $cacheBusterStorage;

    public function __construct(
        SessionAdapterInterface $session,
        CacheBusterStorage      $cacheBusterStorage
    )
    {
        $this->session = $session;
        $this->cacheBusterStorage = $cacheBusterStorage;
    }

    /**
     * Returns the stored token or null if no token is stored.
     * @return AccessToken|null
     */
    public function getToken(): AccessToken|null
    {
        $sessionToken = $this->session->get(self::TOKEN_KEY);
        if ($sessionToken === null) {
            return null;
        }

        $sessionCacheBuster = $this->session->get(self::CACHE_BUSTER_KEY);

        try {
            if ($sessionCacheBuster !== (string)$this->cacheBusterStorage->getCacheBuster()) {
                // Force expiration
                $sessionToken['expires'] = 1349067601;
                unset($sessionToken['expires_in']);
            }

            return new AccessToken($sessionToken);
        } catch (\Throwable) {
            // Decoding issues or invalid token
            $this->clear();
            return null;
        }
    }

    /**
     * Stores the token for later retrieval.
     * @param AccessTokenInterface $token
     * @return void
     */
    public function setToken(AccessTokenInterface $token): void
    {
        $this->session->set(self::TOKEN_KEY, $token->jsonSerialize());
        $this->session->set(self::CACHE_BUSTER_KEY, (string)$this->cacheBusterStorage->getCacheBuster());
    }

    /**
     * Removes the stored token. The token is no longer available after this method is called.
     * @return void
     */
    public function clear(): void
    {
        $this->session->remove(self::TOKEN_KEY);
    }
}
