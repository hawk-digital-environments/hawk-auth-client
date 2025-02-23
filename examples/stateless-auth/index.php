<?php
declare(strict_types=1);

use Hawk\AuthClient\Auth\StatelessAuth;
use Hawk\AuthClient\AuthClient;

Examples::title('Stateless Authentication (e.g. REST APIs)');
Examples::description('
This example shows how to use the stateless authentication.
"STATELESS" means that the user has to provide a valid token with every request.
This is useful for APIs, where the user is not logged in with a session, but with a token.
The token is usually passed in the "Authorization" header.
This example assumes, you already have a token. If you don\'t have one, you can acquire one by a <a href="/stateful-auth">stateful login</a>,
the <a href="/frontend-api">frontend api</a>, or any other Oauth2 flow.
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

    $auth = $client->statelessAuth();

    // This will pass along the auth object into our routs below (implementation specific, your framework might do this differently).
    return [$auth];
});

Examples::route('GET', '/your-api', static function (StatelessAuth $auth) {
    $auth->authenticate(
    // By default, the token is fetched from the "Authorization" header.
    // To make the example work easily in the browser, we use the "token" query parameter.
        $_GET['token'] ?? ''
    );

    if (!$auth->getUser()) {
        http_response_code(401);
        echo json_encode(['error' => 'You are not authenticated']);
        return;
    }

    echo json_encode(['message' => 'You are authenticated as ' . $auth->getUser()->getUsername()]);
});

Examples::route('GET', '/', function (StatelessAuth $auth) {
    Examples::showDescription();
    // The same as above, to get the state of the login
    $auth->authenticate($_GET['token'] ?? '');

    // If the user is authenticated, we have a token.
    $token = $auth->getToken();

    // To make the example work easily in the browser, we use a form to pass the token.
    echo 'Enter a valid OAuth token to authenticate:<br>';
    echo '<form method="get"><input type="text" name="token" placeholder="Token"><button type="submit">Authenticate</button></form>';

    // Show the link to our API
    if ($token === null) {
        echo '<strong>Not authenticated</strong>: You are not logged in<br>';
        echo '<a href="' . Examples::getPageUrl() . '/your-api">Open API endpoint</a>';
    } else {
        echo '<strong>Authenticated</strong>: You are logged in as ' . $auth->getUser()->getUsername() . '<br>';
        echo '<label>Your Token: <input type="text" value="' . $token . '" readonly></label><br>';
        echo '<a href="' . Examples::getPageUrl() . '/your-api?token=' . $token . '">Open API endpoint</a>';
    }
    
    Examples::showBackLink();
});
