<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Auth;


use Hawk\AuthClient\Exception\MissingUserFactoryCollaboratorException;
use Hawk\AuthClient\Users\UserFactory;
use Hawk\AuthClient\Users\Value\User;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;
use SensitiveParameter;

class KeycloakProvider extends AbstractProvider
{
    use BearerAuthorizationTrait;

    protected string $publicKeycloakUrl = '';
    protected string $internalKeycloakUrl = '';
    protected string|null $redirectUriAfterLogout = null;
    protected string $realm = '';
    protected UserFactory $userFactory;

    /**
     * @inheritDoc
     */
    public function __construct(
        #[SensitiveParameter]
        array $options = [],
        array $collaborators = []
    )
    {
        if (!isset($collaborators['userFactory']) || !$collaborators['userFactory'] instanceof UserFactory) {
            throw new MissingUserFactoryCollaboratorException();
        }
        $this->userFactory = $collaborators['userFactory'];

        parent::__construct($options, $collaborators);
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function getBaseAuthorizationUrl(): string
    {
        return $this->getPublicBaseUrlWithRealm() . '/protocol/openid-connect/auth';
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function getBaseAccessTokenUrl(array|null $params = null): string
    {
        return $this->getInternalBaseUrlWithRealm() . '/protocol/openid-connect/token';
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function getResourceOwnerDetailsUrl(AccessToken|null $token = null): string
    {
        return $this->getInternalBaseUrlWithRealm() . '/protocol/openid-connect/userinfo';
    }

    /**
     * Generates a unique logout URL for the user.
     * Also allows to redirect the user to a specific URL after logout.
     *
     * @param AccessToken $token The access token of the user.
     * @param string|null $redirectUrl The URL to redirect the user to after logout. Null to use the default.
     * @return string
     */
    public function getLogoutUrl(AccessToken $token, string|null $redirectUrl = null): string
    {
        $baseUrl = $this->getPublicBaseUrlWithRealm() . '/protocol/openid-connect/logout';
        $query = $this->getAuthorizationQuery(array_filter(
            [
                'id_token_hint' => $token->getValues()['id_token'],
                'post_logout_redirect_uri' => $redirectUrl ?? $this->redirectUriAfterLogout ?? null,
                'client_id' => $this->clientId,
                'state' => $this->getState()
            ]
        ));
        return $this->appendQuery($baseUrl, $query);
    }

    /**
     * Returns the URL to redirect the user after a login (where the code should be converted to a token).
     * @return string
     */
    public function getRedirectUrl(): string
    {
        return $this->redirectUri;
    }

    /**
     * @inheritDoc
     */
    #[\Override] protected function getDefaultScopes(): array
    {
        return ['profile', 'email', 'openid'];
    }

    /**
     * @inheritDoc
     */
    #[\Override] protected function checkResponse(ResponseInterface $response, $data): void
    {
        if (!empty($data['error'])) {
            $error = $data['error'];
            if (isset($data['error_description'])) {
                $error .= ': ' . $data['error_description'];
            }
            throw new IdentityProviderException($error, $response->getStatusCode(), $data);
        }
    }

    /**
     * @inheritDoc
     */
    #[\Override] protected function createResourceOwner(array $response, AccessToken $token): User
    {
        return $this->userFactory->makeUserFromKeycloakData($response);
    }

    protected function getPublicBaseUrlWithRealm(): string
    {
        return $this->getUrlWithRealm($this->publicKeycloakUrl);
    }

    protected function getInternalBaseUrlWithRealm(): string
    {
        return $this->getUrlWithRealm($this->internalKeycloakUrl);
    }

    /**
     * Returns the string that should be used to separate scopes when building
     * the URL for requesting an access token.
     *
     * @return string Scope separator, defaults to ','
     */
    #[\Override] protected function getScopeSeparator(): string
    {
        return ' ';
    }

    private function getUrlWithRealm(string $baseUrl): string
    {
        return $baseUrl . '/realms/' . $this->realm;
    }
}
