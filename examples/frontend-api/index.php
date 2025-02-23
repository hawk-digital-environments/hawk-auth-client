<?php
declare(strict_types=1);


use Hawk\AuthClient\AuthClient;
use Hawk\AuthClient\Cache\NullCacheAdapter;

Examples::title('Frontend API (SPA with stateless api)');
Examples::description('
This example shows you how you can easily integrate the auth client with your frontend application.
The general idea is, that you probably have the auth client running in your backend to authenticate api requests anyway,
so the frontend api allows your frontend to authenticate the user and fetch user data directly, without the need to implement
custom endpoints yourself.
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
        cache: new NullCacheAdapter()
    );

    // This will pass along the auth object into our routs below (implementation specific, your framework might do this differently).
    return [$client];
});

Examples::route(['GET', 'POST'], '/auth-api', static function (AuthClient $client) {
    // The frontend api comes with its own request handler, that will handle the requests
    // sent by the "@hawk-hhg/auth-client" npm package. To allow the javascript access
    // to the api, you create a route in your framework and call the "handle" method on the frontendApi.
    // Note that there are both GET and POST requests that are sent to this endpoint!

    // If your frontend only wants to authenticate the user, you can use the following code:
    // $client->frontendApi()->handle();

    // If your framework expects a response, you can use the following code:
    // $response = $client->frontendApi()
    //     // The response factory is a versatile tool, to create a response object that fits your framework.
    //     // It receives three parameters:
    //     // 1. The data that should be sent to the frontend, this is always an array, that is expected by the javascript to be json encoded.
    //     // 2. The headers that should be sent to the frontend, this is always an array, where the keys are the header names and the values the header values.
    //     // 3. The HTTP status code that should be sent to the frontend, this is always an integer.
    //     ->setResponseFactory(function(array $data, array $headers, int $statusCode): YourResponse {
    //         return (new YourResponse())
    //             ->withHeaders($headers)
    //             ->withBody(json_encode($data))
    //             ->withStatus($statusCode);
    //     })
    // ->handle();

    // There are also additional features you can enable,
    // like fetching the user profile, permissions, and more.
    // All features are optional, if you are trying to access a feature that is not enabled,
    // the frontend script will tell you which feature is missing.
    $client->frontendApi()
        // This will enable the user info endpoint, that will return the user data.
        // This enables the frontend to see the data of the currently logged-in user.
        ->enableUserInfo()
        // This will enable the user profile endpoint, it gives the frontend
        // the ability to read the profile data and structure of the currently loggend-in user.
        ->enableUserProfile()
        // This will enable the "guard" feature, that allows your frontend to check for user
        // permissions, roles and groups.
        ->enablePermissions()
        ->handle();
});

Examples::route('GET', '/your-api', static function (AuthClient $client) {
    // This is the normal stateless authentication flow.
    $auth = $client->statelessAuth();
    $auth->authenticate();

    header('Content-Type: application/json');
    if ($auth->getUser() === null) {
        http_response_code(401);
        echo json_encode(['error' => 'You are not authenticated']);
        return;
    }

    echo json_encode(['message' => 'You are authenticated as ' . $auth->getUser()->getUsername()]);
});

Examples::route('GET', '/', static function () {
    // Render the index.html by injecting some placeholders into the template
    $placeholders = [
        '{{PAGE_URL}}' => Examples::getPageUrl(),
        '{{DESCRIPTION}}' => Examples::getDescription(),
        '{{TITLE}}' => Examples::getTitle(),
    ];
    echo str_replace(
        array_keys($placeholders),
        $placeholders,
        file_get_contents(__DIR__ . '/index.html')
    );
});
