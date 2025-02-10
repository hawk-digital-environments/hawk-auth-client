<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Keycloak\Value;


use Hawk\AuthClient\Exception\InvalidInternalKeycloakUrlException;
use Hawk\AuthClient\Exception\InvalidPublicKeycloakUrlException;
use Hawk\AuthClient\Exception\InvalidRedirectUrlException;
use JsonException;
use SensitiveParameter;

/**
 * @internal This class is not part of the public API and may change at any time.
 */
readonly class ConnectionConfig
{
    protected string $redirectUrl;
    protected ?string $redirectUrlAfterLogout;
    protected string $publicKeycloakUrl;
    protected string $internalKeycloakUrl;
    protected string $clientId;
    protected string $clientSecret;
    protected string $realm;

    /**
     * @throws JsonException
     */
    public function __construct(
        string      $redirectUrl,
        string|null $redirectUrlAfterLogout,
        string      $publicKeycloakUrl,
        string|null $internalKeycloakUrl,
        #[SensitiveParameter]
        string      $clientId,
        #[SensitiveParameter]
        string      $clientSecret,
        string      $realm
    )
    {
        /** @noinspection BypassedUrlValidationInspection */
        if (empty($redirectUrl) || !filter_var($redirectUrl, FILTER_VALIDATE_URL)) {
            throw new InvalidRedirectUrlException($redirectUrl);
        }
        /** @noinspection BypassedUrlValidationInspection */
        if (empty($publicKeycloakUrl) || !filter_var($publicKeycloakUrl, FILTER_VALIDATE_URL)) {
            throw new InvalidPublicKeycloakUrlException($publicKeycloakUrl);
        }
        /** @noinspection BypassedUrlValidationInspection */
        if ($internalKeycloakUrl !== null && !filter_var($internalKeycloakUrl, FILTER_VALIDATE_URL)) {
            throw new InvalidInternalKeycloakUrlException($internalKeycloakUrl);
        }

        $endBaseWithoutSlash = static function (string $url): string {
            return rtrim($url, '/');
        };

        $this->redirectUrl = $redirectUrl;
        $this->redirectUrlAfterLogout = $redirectUrlAfterLogout;
        $this->publicKeycloakUrl = $endBaseWithoutSlash($publicKeycloakUrl);
        $this->internalKeycloakUrl = $endBaseWithoutSlash($internalKeycloakUrl ?? $publicKeycloakUrl);
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->realm = $realm;
    }

    public function getRedirectUrl(): string
    {
        return $this->redirectUrl;
    }

    public function getRedirectUrlAfterLogout(): ?string
    {
        return $this->redirectUrlAfterLogout;
    }

    public function getPublicKeycloakUrl(): string
    {
        return $this->publicKeycloakUrl;
    }

    public function getInternalKeycloakUrl(): string
    {
        return $this->internalKeycloakUrl;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    public function getRealm(): string
    {
        return $this->realm;
    }

    public function getHash(): string
    {
        return hash('sha256', json_encode(get_object_vars($this), JSON_THROW_ON_ERROR));
    }

    public function __debugInfo(): array
    {
        return [
            'redirectUrl' => $this->redirectUrl,
            'publicKeycloakUrl' => $this->publicKeycloakUrl,
            'internalKeycloakUrl' => $this->internalKeycloakUrl,
            'clientId' => '***',
            'clientSecret' => '***',
            'realm' => $this->realm
        ];
    }
}
