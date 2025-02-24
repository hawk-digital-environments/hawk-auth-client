<?php
declare(strict_types=1);

use Hawk\AuthClient\AuthClient;
use Hawk\AuthClient\Users\Value\User;
use Hawk\AuthClient\Users\Value\UserConstraints;

Examples::title('Reading users');
Examples::description('
This example shows multiple use cases of interacting with the users known in the system.
Those features are out of scope for normal "authentication", but allow the usage of keycloak as a full user handling backend.
');

// This example uses stateful authentication (session-based) which you can learn more about in the "stateful-auth" example.
// This means the user stays logged in across page loads using a session cookie.
Examples::bootstrapStatefulAuth();

Examples::route('GET', '/all', function (AuthClient $client) {
    renderBackToListLink();

    $pageSize = 20;
    $offset = getPaginationOffset();

    echo '<p>Here you see a list of <strong>all</strong> users in the system. The list is paginated, so you can navigate through the list.
The list is streamed directly from Keycloak in alphabetical order. Currently, there is no way to get the number of entries in the list
or to sort the list in another way. If needed those features could be added easily in the future.</p>';
    // You can use the "users" layer to access multiple user functions, like getting all users.
    // The returned list is a lazy loading iterator, so it will only fetch the users when you iterate over it.
    // You can also set the offset and limit of the query for pagination.
    echo '<ul>';
    foreach ($client->users()->getAll()->setOffset($offset)->setLimit($pageSize) as $user) {
        renderUserLine($user);
    }
    echo '</ul>';

    renderPaginationLinks($offset, $pageSize);
});

Examples::route('GET', '/filtered', function (AuthClient $client) {
    renderBackToListLink();

    echo '<p>When retrieving users from keycloak you can use the "constraints" to limit what kind of users you want to see.
In this example we limit the list to all users that have the string "ann" in their first-, or lastname</p>';
    echo '<ul>';
    // The constraints can be used to filter the list of users even further.
    foreach ($client->users()->getAll((new UserConstraints())->withSearch('ann')) as $user) {
        renderUserLine($user);
    }
    echo '</ul>';
});

Examples::route('GET', '/online', function (AuthClient $client) {
    renderBackToListLink();

    echo '<p>Using the "constraints" you can also limit the list of users to only those that are currently online.
If this list is empty, try logging in with one of the other examples (like <a href="/stateful-auth" target="_blank">Stateful auth</a>) and then come back.
Note, due to aggressive caching, you can experience up to 20 seconds delay on updates of this list.</p>';
    $empty = true;
    echo '<ul>';
    foreach ($client->users()->getAll((new UserConstraints())->withOnlyOnline()) as $user) {
        $empty = false;
        renderUserLine($user);
    }
    if ($empty) {
        echo '<li><strong>No users online</strong></li>';
    }
    echo '</ul>';
});

Examples::route('GET', '/one', function (AuthClient $client) {
    renderBackToListLink();

    echo 'You can also get a single user by its UUID. This is useful if you have the id of a user and want to get the user object.';

    $user = $client->users()->getOne('f0e6b98b-5287-476d-b341-2141e8ca0f5c');
    if (!$user) {
        echo '<p>User not found</p>';
        return;
    }

    echo '<h3>' . $user->getUsername() . '</h3>';
    echo '<pre>';
    var_export($user->toArray());
    echo '</pre>';
});

Examples::route('GET', '/', function () {
    Examples::showDescription();
    echo '<ul>';
    echo '<li><a href="' . Examples::getPageUrl() . '/all">List all users</a></li>';
    echo '<li><a href="' . Examples::getPageUrl() . '/filtered">List filtered users</a></li>';
    echo '<li><a href="' . Examples::getPageUrl() . '/online">List online users</a></li>';
    echo '<li><a href="' . Examples::getPageUrl() . '/one">Get one user</a></li>';
    echo '</ul>';
    Examples::showBackLink();
});


// =====================================================================================================================
// Helper functions, not really important for the example.
// =====================================================================================================================
function renderUserLine(User $user): void
{
    echo '<li>' . $user->getUsername() . ' - ' .
        $user->getClaims()->get('given_name') . ' ' . $user->getClaims()->get('family_name') . '</li>';
}

function getPaginationOffset()
{
    return max(0, (int)($_GET['offset'] ?? 0));
}

function renderBackToListLink(): void
{
    echo '<a href="' . Examples::getPageUrl() . '">Back to list</a>';
    echo '<hr>';
}

function renderPaginationLinks(int $offset, int $pageSize): void
{
    echo '<hr>';
    echo '<p>';
    if ($offset > 0) {
        echo '<a href="' . Examples::getRouteUrl() . '?offset=' . ($offset - $pageSize) . '">Previous</a> | ';
    }
    echo '<a href="' . Examples::getRouteUrl() . '?offset=' . ($offset + $pageSize) . '">Next</a>';
    echo '</p>';
}
