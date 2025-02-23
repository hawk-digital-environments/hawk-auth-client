<?php
declare(strict_types=1);

use Hawk\AuthClient\Auth\StatefulAuth;
use Hawk\AuthClient\AuthClient;

Examples::title('Stateful Authentication (e.g. Server rendered websites)');
Examples::description('
This example shows how to use the stateful authentication in your code.
It is deliberately kept simple, and verbose to show the steps needed to authenticate a user.
"STATEFUL" means that the user is stored in the session, so the credentials only need to be presented once.
If you do want to force the user to log in, take a look at the <a href="/stateful-auth-force-login">stateful-auth-force-login</a> example.
');

Examples::bootstrap(static function () {
    Examples::includeComposerAutoload();

    // This creates a new connection with the Keycloak instance.
    // getenv() is used to get the environment variables, you can also use $_ENV or $_SERVER or hardcode the values (NOT RECOMMENDED).
    // The variables are currently defined in the docker-compose.yml file.
    $client = new AuthClient(
        redirectUrl: Examples::getPageUrl() . '/callback',
        publicKeycloakUrl: getenv('PUBLIC_KEYCLOAK_URL'),
        realm: getenv('REALM'),
        clientId: getenv('CLIENT_ID'),
        clientSecret: getenv('CLIENT_SECRET'),
        // This is optional, if you want to talk with Keycloak on another url directly than the user. E.g. in a docker environment.
        internalKeycloakUrl: empty(getenv('INTERNAL_KEYCLOAK_URL')) ? null : getenv('INTERNAL_KEYCLOAK_URL'),
    );

    // This creates a new stateful authentication flow you use to authenticate the user.
    $auth = $client->statefulAuth();

    // THIS IS IMPORTANT: Start the session before doing the authentication.
    session_start();

    // Authenticate the user, this will try to get the user from the session.
    $auth->authenticate();

    // This will pass along the auth object into our routs below (implementation specific, your framework might do this differently).
    return [$auth];
});

Examples::route('GET', '/callback', static function (StatefulAuth $auth) {
    // HandleLogin will redirect the user to the Keycloak login page, AND handle the callback from Keycloak.
    $auth->handleCallback(
    // After the login was successful, the user will be redirected to this URL.
        onHandled: fn() => Examples::getPageUrl(),
    );
});

Examples::route('GET', '/login', static function (StatefulAuth $auth) {
    // Redirect the user to the Keycloak login page.
    $auth->login();
});

Examples::route('GET', '/logout', static function (StatefulAuth $auth) {
    // Logout the user and redirect to the given URL.
    $auth->logout(Examples::getPageUrl());
});

Examples::route('GET', '/', function (StatefulAuth $auth) {
    Examples::showDescription();

    // If the user is authenticated, you can get the user object, otherwise the user is null.
    $user = $auth->getUser();

    if ($user === null) {
        // Display a login link if the user is not authenticated.
        echo '<strong>Not authenticated</strong>: You are not logged in<br>';
        echo '<a href="' . Examples::getPageUrl() . '/login">Login</a>';
        Examples::showBackLink();
        return;
    }

    // If the user is authenticated, you can show the user data.
    echo '<strong>Authenticated</strong>: You are logged in as ' . $user->getUsername() . '<br>';
    echo '<label>Your Token: <input type="text" value="' . $auth->getToken()->getToken() . '" readonly></label><br>';
    echo '<a href="' . Examples::getPageUrl() . '/logout">Logout</a>';
    Examples::showBackLink();
});
