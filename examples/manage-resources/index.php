<?php

// This example uses stateful authentication (session-based) which you can learn more about in the "stateful-auth" example.
// This means the user stays logged in across page loads using a session cookie.
use Hawk\AuthClient\Auth\StatefulAuth;
use Hawk\AuthClient\AuthClient;
use Hawk\AuthClient\Resources\Value\ResourceConstraints;

Examples::title('Manage resources');

Examples::description('
This example shows you how you can manage resources with the auth client.
Resources are a way to define access to certain parts of your application.
They are a generic construct you can creatively use to define access to anything you want.
A "resource" is basically a "thing" you want to protect, like a file, a database entry, or a page,
and the "scopes" describe what users can do with this "thing".
');

// Define available scopes for this example. In your application, these would represent
// the actual permissions users can have on resources (like read, write, admin, etc.)
// Those scopes are part of YOUR application, so it makes sense to namespace them to make them unique.
Examples::$context = [
    'scopes' => ['example:read', 'example:write', 'example:delete'],
];

// This example uses stateful authentication (session-based) which you can learn more about in the "stateful-auth" example.
// This means the user stays logged in across page loads using a session cookie.
Examples::bootstrapStatefulAuth();

Examples::route('POST', '/share', static function (AuthClient $client, StatefulAuth $auth) {
    // Get the resource by its ID from the form submission
    // The resources()->getOne() method returns null if the resource doesn't exist
    $resource = $client->resources()->getOne($_POST['resource'] ?? '');
    if (!$resource) {
        echo 'Resource not found.';
        return;
    }

    // Resources can only be managed by their owners. The auth client provides
    // helper methods to verify resource ownership.
    // First check if we have a logged-in user at all
    $user = $auth->getUser();
    if (!$user) {
        echo 'You need to be logged in to share a resource.';
        return;
    }

    // Then verify the logged-in user owns this resource by comparing IDs
    if ((string)$resource->getOwner()->getId() !== (string)$user->getId()) {
        echo 'You are not the owner of this resource.';
        return;
    }

    // Get the user we want to share with by their ID from the form
    // Similar to resources, users()->getOne() returns null if user not found
    $userToShareWith = $client->users()->getOne($_POST['user'] ?? '');
    if (!$userToShareWith) {
        echo 'No user found to share the resource with.';
        return;
    }
    // Prevent sharing with yourself as it doesn't make sense - you already own it
    if ($userToShareWith->getId() === $user->getId()) {
        echo 'You cannot share a resource with yourself.';
        return;
    }

    // Get the selected scopes from the multi-select form field
    // These define what permissions the user will have on the resource
    $scopes = $_POST['scopes'] ?? [];
    if (empty($scopes)) {
        echo 'Please select at least one scope to share.';
        return;
    }

    // Verify that all selected scopes are actually available on the resource
    // You can't grant permissions that the resource doesn't support
    if (count(array_intersect($scopes, $resource->getScopes()?->jsonSerialize() ?? [])) !== count($scopes)) {
        echo 'You cannot share a resource with scopes that are not part of the resource.';
        return;
    }

    // Share the resource with another user by granting them specific scopes
    // This creates or updates the user's permissions on the resource
    $client->resources()->shareWithUser($resource, $userToShareWith, $scopes);
    header('Location: ' . Examples::getPageUrl());
});

Examples::route('GET', '/share', static function (AuthClient $client, StatefulAuth $auth) {
    $user = $auth->getUser();
    if (!$user) {
        echo 'You need to be logged in to share a resource.';
        return;
    }

    // Form to share a resource with another user
    echo '<form method="post">';
    echo '<p>';
    // Resource selector - shows all resources owned by current user
    // The display includes both the friendly name and available scopes
    echo '<label for="resource">Resource:</label><br>';
    echo '<select id="resource" name="resource" required>';
    foreach ($client->resources()->getAll((new ResourceConstraints())->withOwner($user)) as $resource) {
        echo '<option value="' . $resource->getId() . '">' . $resource->getDisplayName() . ' (' . implode(',', $resource->getScopes()?->jsonSerialize() ?? []) . ')</option>';
    }
    echo '</select>';
    echo '</p><p>';
    // User selector - shows all available users in the system
    echo '<label for="user">User:</label><br>';
    echo '<select id="user" name="user" required>';
    foreach ($client->users()->getAll() as $user) {
        echo '<option value="' . $user->getId() . '">' . $user->getUsername() . '</option>';
    }
    echo '</select>';
    echo '</p><p>';
    // Scope selector - multi-select field allowing multiple permissions
    // These scopes determine what the user can do with the resource
    echo '<label for="scopes">Scopes:</label><br>';
    echo '<select id="scopes" name="scopes[]" multiple required>';
    foreach (Examples::$context['scopes'] as $scope) {
        echo '<option value="' . $scope . '">' . $scope . '</option>';
    }
    echo '</select>';
    echo '</p><p>';
    echo '<button type="submit">Share resource</button>';
    echo '</p></form>';
});

Examples::route('GET', '/unshare', static function (AuthClient $client, StatefulAuth $auth) {
    // Similar to sharing, first get the resource and verify ownership
    $resource = $client->resources()->getOne($_GET['resource'] ?? '');
    if (!$resource) {
        echo 'Resource not found.';
        return;
    }

    $user = $auth->getUser();
    if (!$user) {
        echo 'You need to be logged in to unshare a resource.';
        return;
    }

    if ((string)$resource->getOwner()->getId() !== (string)$user->getId()) {
        echo 'You are not the owner of this resource.';
        return;
    }

    // Get the user whose access we want to remove
    $userToUnshareWith = $client->users()->getOne($_GET['user'] ?? '');
    if (!$userToUnshareWith) {
        echo 'No user found to unshare the resource from.';
        return;
    }

    // Passing null as scopes removes all access for the user
    $client->resources()->shareWithUser($resource, $userToUnshareWith, null);
    header('Location: ' . Examples::getPageUrl());
});

Examples::route('POST', '/create', static function (AuthClient $client, StatefulAuth $auth) {
    // Get form data for the new resource
    // - name: Internal identifier for the resource
    // - displayName: User-friendly name shown in the UI
    // - scopes: What operations are allowed on this resource
    $name = $_POST['name'] ?? '';
    $displayName = $_POST['displayName'] ?? '';
    $scopes = $_POST['scopes'] ?? [];

    // All fields are required for resource creation
    if (empty($name) || empty($displayName) || empty($scopes)) {
        echo 'Please fill out all fields.';
        return;
    }

    // The resource builder provides a fluent interface to define resources
    // You can update an existing resource by using the same name or resource id
    // This is useful for changing resource properties or adding/removing scopes
    $client->resources()->define($name)
        ->setName($name)
        ->setDisplayName($displayName)
        ->addScope(...$scopes)
        ->setOwner($auth->getUser())
        ->save();

    header('Location: ' . Examples::getPageUrl());
});

Examples::route('GET', '/create', static function () {
    // Form for creating a new resource
    echo '<form method="post">';
    echo '<p>';
    // Name field - used as internal identifier
    echo '<label for="name">Resource name*:</label><br>';
    echo '<input type="text" id="name" name="name" required>';
    echo '</p><p>';
    // Display name field - shown to users in the UI
    echo '<label for="displayName">Resource display name*:</label><br>';
    echo '<input type="text" id="displayName" name="displayName" required>';
    echo '</p><p>';
    // Scope selector - defines what operations are allowed on this resource
    echo '<label for="scopes">Resource scopes*:</label><br>';
    echo '<select id="scopes" name="scopes[]" multiple required>';
    foreach (Examples::$context['scopes'] as $scope) {
        echo '<option value="' . $scope . '">' . $scope . '</option>';
    }
    echo '</select>';
    echo '</p><p>';
    echo '<button type="submit">Create resource</button>';
    echo '</p></form>';

    // This JavaScript automatically sets the display name to match the resource name
    // as you type, unless you've manually changed the display name
    echo <<<HTML
<script>
(function(){
    let displayNameChanged = false;
    const displayNameElement = document.getElementById('displayName');
    const nameElement = document.getElementById('name');
    
    // Copy the resource name to display name if it hasn't been manually changed
    const setDisplayNameToName = function(){
        if(!displayNameChanged) {
            displayNameElement.value = nameElement.value;
        }
    };
    
    // Mark display name as changed when user modifies it
    displayNameElement.addEventListener('change', function() {
        displayNameChanged = displayNameElement.value !== '';
        setDisplayNameToName();
    });
    
    // Update display name when resource name changes (if not manually changed)
    nameElement.addEventListener('input', setDisplayNameToName);
})();
</script>
HTML;
});

Examples::route('GET', '/delete', static function (AuthClient $client, StatefulAuth $auth) {
    // Get the resource and verify ownership before deletion
    $resource = $client->resources()->getOne($_GET['resource'] ?? '');
    if (!$resource) {
        echo 'Resource not found.';
        return;
    }

    $user = $auth->getUser();
    if (!$user) {
        echo 'You need to be logged in to delete a resource.';
        return;
    }

    if ((string)$resource->getOwner()->getId() !== (string)$user->getId()) {
        echo 'You are not the owner of this resource.';
        return;
    }

    // Remove the resource and all associated permissions
    // This automatically removes all user access assignments
    $client->resources()->remove($resource);
    header('Location: ' . Examples::getPageUrl());
});

Examples::route('GET', '/', static function (AuthClient $client, StatefulAuth $auth) {
    Examples::showDescription();

    $user = $auth->getUser();
    if (!$user) {
        echo '<p>You are currently not logged in, to see a profile you need to <a href="' . Examples::getPageUrl() . '/login">login</a> first.</p>';
        Examples::showBackLink();
        return;
    }

    echo '<p>Click <a href="' . Examples::getPageUrl() . '/create">here</a> to create a new resource.</p>';
    echo '<p>Click <a href="' . Examples::getPageUrl() . '/share">here</a> to share a resource with another user.</p>';

    // List all resources owned by the current user
    // ResourceConstraints->withOwner filters to show only resources owned by this user
    echo '<p>This is the list of all resources, that are owned by ' . $user->getUsername() . '.</p>';
    ob_start();
    foreach ($client->resources()->getAll((new ResourceConstraints())->withOwner($user)) as $resource) {
        echo '<li>';
        echo '<strong>' . $resource->getDisplayName() . '</strong> (' . $resource->getName() . ' | ' . $resource->getId() . ')';
        echo ' <a href="' . Examples::getPageUrl() . '/delete?resource=' . $resource->getId() . '">Delete</a>';

        // For each resource, show users who have access and their scopes
        // getResourceUsers returns all users who have any permissions on this resource
        ob_start();
        foreach ($client->users()->getResourceUsers($resource) as $user) {
            echo '<li>';
            echo $user->getUsername() . ' (' . implode(', ', $user->getScopes()->jsonSerialize()) . ')';
            echo ' <a href="' . Examples::getPageUrl() . '/unshare?resource=' . $resource->getId() . '&user=' . $user->getId() . '">Unshare</a>';
            echo '</li>';
        }
        $userList = ob_get_clean();
        if (!empty($userList)) {
            echo '<br>';
            echo 'Users with access:';
            echo '<ul>' . $userList . '</ul>';
        }

        echo '</li>';
    }

    $list = ob_get_clean();
    if (empty($list)) {
        echo '<p>There are no resources owned by you. Maybe, <a href="' . Examples::getPageUrl() . '/create">create</a> one?" </p>';
    } else {
        echo '<ul>' . $list . '</ul>';
    }

    Examples::showBackLink();
});
