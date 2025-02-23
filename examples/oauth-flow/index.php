<?php
declare(strict_types=1);

use Hawk\AuthClient\Auth\KeycloakProvider;
use Hawk\AuthClient\AuthClient;

Examples::title('League OAuth2 client');
Examples::description('
Under the hood, our client uses the <a href="https://oauth2-client.thephpleague.com/" target="_blank">PHP League OAuth2 client</a>.
This means, if you need a more control over the OAuth flow, you can use the OAuth2 client directly.
This example is a direct copy of the <a href="https://oauth2-client.thephpleague.com/usage/" target="_blank">PHP League OAuth2 client</a> example.
');

Examples::bootstrap(static function () {
    Examples::includeComposerAutoload();

    // This creates a new connection with the Keycloak instance.
    // getenv() is used to get the environment variables, you can also use $_ENV or $_SERVER or hardcode the values (NOT RECOMMENDED).
    // The variables are currently defined in the docker-compose.yml file.
    $client = new AuthClient(
        redirectUrl: Examples::getPageUrl(),
        publicKeycloakUrl: getenv('PUBLIC_KEYCLOAK_URL'),
        realm: getenv('REALM'),
        clientId: getenv('CLIENT_ID'),
        clientSecret: getenv('CLIENT_SECRET'),
        // This is optional, if you want to talk with Keycloak on another url directly than the user. E.g. in a docker environment.
        internalKeycloakUrl: empty(getenv('INTERNAL_KEYCLOAK_URL')) ? null : getenv('INTERNAL_KEYCLOAK_URL'),
    );

    // This returns the internal OAuth provider, which is a default League OAuth2 client.
    $oauth = $client->oauthProvider();

    // THIS IS IMPORTANT: Start the session before doing the authentication.
    session_start();

    // This will pass along the auth object into our routs below (implementation specific, your framework might do this differently).
    return [$oauth];
});

Examples::route('GET', '/', static function (KeycloakProvider $oauth) {
    // If we don't have an authorization code then get one
    if (!isset($_GET['code'])) {

        // Fetch the authorization URL from the provider; this returns the
        // urlAuthorize option and generates and applies any necessary parameters
        // (e.g. state).
        $authorizationUrl = $oauth->getAuthorizationUrl();

        // Get the state generated for you and store it to the session.
        $_SESSION['oauth2state'] = $oauth->getState();

        // Optional, only required when PKCE is enabled.
        // Get the PKCE code generated for you and store it to the session.
        $_SESSION['oauth2pkceCode'] = $oauth->getPkceCode();

        // Redirect the user to the authorization URL.
        header('Location: ' . $authorizationUrl);
        exit;

        // Check given state against previously stored one to mitigate CSRF attack
    } elseif (empty($_GET['state']) || empty($_SESSION['oauth2state']) || $_GET['state'] !== $_SESSION['oauth2state']) {
        if (isset($_SESSION['oauth2state'])) {
            unset($_SESSION['oauth2state']);
        }

        exit('Invalid state');
    } else {
        try {
            // Optional, only required when PKCE is enabled.
            // Restore the PKCE code stored in the session.
            $oauth->setPkceCode($_SESSION['oauth2pkceCode']);

            // Try to get an access token using the authorization code grant.
            $tokens = $oauth->getAccessToken('authorization_code', [
                'code' => $_GET['code']
            ]);

            Examples::showDescription();

            // We have an access token, which we may use in authenticated
            // requests against the service provider's API.
            echo '<strong>Access Token:</strong> <input type="text" readonly value="' . $tokens->getToken() . '"><br>';
            echo '<strong>Refresh Token:</strong> <input type="text" readonly value="' . $tokens->getRefreshToken() . '"><br>';
            echo '<strong>Expired in:</strong> <input type="text" readonly value="' . $tokens->getExpires() . '"><br>';
            echo '<strong>Already expired?</strong> ' . ($tokens->hasExpired() ? 'expired' : 'not expired') . '<br>';

            // Using the access token, we may look up details about the
            // resource owner.
            $resourceOwner = $oauth->getResourceOwner($tokens);

            echo '<strong>Resource Owner/User:</strong><br>';
            echo '<pre>';
            var_export($resourceOwner->toArray());
            echo '</pre>';

            Examples::showBackLink();
        } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
            // Failed to get the access token or user details.
            exit($e->getMessage());
        }
    }
});
