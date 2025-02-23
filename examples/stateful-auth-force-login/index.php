<?php
declare(strict_types=1);

use Hawk\AuthClient\Auth\StatefulAuth;
use Hawk\AuthClient\AuthClient;

Examples::title('Stateful Authentication (forced login)');
Examples::description('
Like the normal <a href="/stateful-auth">stateful authentication</a>, this example shows how to use the stateful authentication in your code.
But in this case, the user is forced to log in before accessing the page. While there are multiple options to achieve this,
here we use the quality of life function "authenticateOrLogin" to force the user to log in.
');

Examples::bootstrap(static function () {
    // This is the autoload file, it is required to load the classes from the vendor directory.
    require VENDOR_AUTOLOAD_PATH;

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

    // This creates a new stateful authentication flow you use to authenticate the user.
    $auth = $client->statefulAuth();

    // THIS IS IMPORTANT: Start the session before doing the authentication.
    session_start();

    // This is a special quality of life function that will both authenticate the user
    // and redirect the user to the login page if the user is not authenticated. It will also automatically handle the login
    // callback from Keycloak.
    // After the login is completed the user will be redirected to the "redirectUrl" from the AuthClient.
    $auth->authenticateOrLogin();

    // This will pass along the auth object into our routs below (implementation specific, your framework might do this differently).
    return [$auth];
});

Examples::route('GET', '/logout', static function (StatefulAuth $auth) {
    // Logout the user and redirect to the given URL.
    $auth->logout(Examples::getPageUrl());
});

Examples::route('GET', '/', static function (StatefulAuth $auth) {
    // If the user is authenticated, you can show the user data.
    echo '<h2>Authenticated</h2>';
    echo 'You are logged in as ' . $auth->getUser()->getUsername() . '<br>';
    echo '<a href="' . Examples::getPageUrl() . '/logout">Logout</a>';
});
