<?php
declare(strict_types=1);

use Hawk\AuthClient\Auth\StatefulAuth;
use Hawk\AuthClient\AuthClient;
use Hawk\AuthClient\Exception\ProfileUpdateDataInvalidException;
use Hawk\AuthClient\Exception\ProfileUpdateFailedException;
use Hawk\AuthClient\Profiles\Structure\FieldInputTypes;
use Hawk\AuthClient\Profiles\Structure\ProfileField;
use Hawk\AuthClient\Profiles\Structure\ProfileStructure;
use Hawk\AuthClient\Users\Value\User;

Examples::title('User Profile Management');
Examples::description('
This example demonstrates how to work with user profiles in the HAWK system.
It shows how to: Define custom profile fields with different types (text, date, select, etc.),
Group fields into logical sections, Handle profile viewing and editing, Switch between user and admin views,
Validate and update profile data

The profile system allows you to store additional information about your users beyond 
their basic account details. You can create custom fields, organize them into groups,
and control who can view or edit each field.
');

// This example uses stateful authentication (session-based) which you can learn more about in the "stateful-auth" example.
// This means the user stays logged in across page loads using a session cookie.
Examples::bootstrapStatefulAuth();

// This route handles the initial setup of our profile structure.
// It defines what fields are available and how they should be configured.
Examples::route('GET', '/define', static function (AuthClient $client) {
    // Start defining the profile structure - this is where we set up all our custom fields
    $structure = $client->profile()->define();

    // Create a group called "personal" to organize related fields together
    // Groups help keep the profile organized and make it easier for users to find fields
    $group = $structure->getGroup('personal')
        ->setDisplayName('Personal Information');

    // Define a "bio" field for users to write about themselves
    // This demonstrates a textarea field that only the user can see and edit
    $structure->getField('bio')
        ->setDisplayName('About me')
        ->setGroup($group)
        ->setInputType(FieldInputTypes::TEXTAREA)
        ->setAdminCanView(false)
        ->setAdminCanEdit(false)
        ->setHelperTextAfter('Tell us something about yourself.');

    // Add a birthday field using the DATE input type
    // This shows how to use built-in date validation
    $structure->getField('birthday')
        ->setDisplayName('Birthday')
        ->setGroup($group)
        ->setInputType(FieldInputTypes::DATE)
        ->setDateValidator();

    // Create a select field with predefined options
    // This shows how to create a required field with specific valid options
    $structure->getField('favoriteColor')
        ->setDisplayName('Favorite color')
        ->setGroup($group)
        ->setRequiredForUser()
        ->setInputType(FieldInputTypes::SELECT)
        ->setOptionsValidator(['red', 'green', 'blue']);

    // Example of a field that's hidden from users
    // The second parameter 'false' means this is a global field (not client-specific)
    $structure->getField('profilePicture', false)
        ->setDisplayName('Profile picture')
        ->setInputType(FieldInputTypes::URL)
        ->setUserCanEdit(false)
        ->setUserCanView(false);

    // Example of a client-specific field
    // The second parameter 'messenger' indicates this field belongs to the messenger client
    $structure->getField('messenger-name', 'messenger')
        ->setDisplayName('Messenger name')
        ->setInputType(FieldInputTypes::TEXT)
        ->isRequired();

    $structure->save();

    header('Location: ' . Examples::getPageUrl());
});

// This route handles the form submission when updating a profile
// It shows how to process profile updates and handle validation errors
Examples::route('POST', '/edit', static function (AuthClient $client, StatefulAuth $auth) {
    $user = $auth->getUser();
    if (!$user) {
        echo 'Unauthorized';
        exit;
    }

    try {
        // Create a profile updater - the second parameter determines if we're updating as an admin
        // This affects which fields we can modify based on the field permissions
        $updater = $client->profile()->update($user, $_POST['__mode'] === 'admin');
        unset($_POST['__mode']);
        foreach ($_POST as $key => $value) {
            $updater->set(fieldNameToFullName($key), $value, false);
        }
        $updater->save();
    } catch (ProfileUpdateDataInvalidException $e) {
        echo '<p>Failed to update profile: ' . $e->getMessage() . '</p>';
        foreach ($e->getErrors() as $error) {
            echo '<p>' . $error->getField() . ': ' . $error->getMessage() . '</p>';
        }
    } catch (ProfileUpdateFailedException $e) {
        echo '<p>Failed to update profile: ' . $e->getMessage() . '</p>';
    }

    echo '<p>Profile updated successfully</p>';
    echo '<p><a href="' . Examples::getPageUrl() . '">Back to profile</a></p>';
});

// This route displays the profile editing form
// It demonstrates how to render different fields based on user/admin permissions
Examples::route('GET', '/edit', static function (AuthClient $client, StatefulAuth $auth) {
    $user = $auth->getUser();
    if (!$user) {
        echo '<p>You are currently not logged in, to see a profile you need to <a href="' . Examples::getPageUrl() . '/login">login</a> first.</p>';
        return;
    }

    // You can access the user profile in two ways:
    // By default, using the $user->getProfile() method, you will get the profile as the user sees it.
    // But sometimes, you might want to see the profile as an admin, which depending on your configuration might show more fields.
    $viewMode = showViewModeSwitcher();
    if ($viewMode === 'admin') {
        // The getOneAsAdmin method will return the profile as an admin sees it.
        // This includes both the values and the structure of the profile.
        $profileStructure = $client->profile()->getOneAsAdmin($user)->getStructure();
    } else {
        // The user will always return the profile as that user should see it.
        $profileStructure = $user->getProfile()->getStructure();
    }

    echo '<form method="post">';
    echo '<input type="hidden" name="__mode" value="' . $viewMode . '">';

    showFieldEditors($user, $profileStructure);

    echo '<button type="submit">Save</button>';
    echo '</form>';
});

// The main route that shows the current profile state
// It demonstrates how to display profile information and handle different view modes
Examples::route('GET', '/', static function (AuthClient $client, StatefulAuth $auth) {
    Examples::showDescription();

    echo '<p>Click <a href="' . Examples::getPageUrl() . '/define">here</a> to define some example fields.</p>';
    echo '<p>Click <a href="' . Examples::getPageUrl() . '/edit">here</a> to edit your profile.</p>';

    $user = $auth->getUser();
    if (!$user) {
        echo '<p>You are currently not logged in, to see a profile you need to <a href="' . Examples::getPageUrl() . '/login">login</a> first.</p>';
        return;
    }

    // You can access the user profile in two ways:
    // By default, using the $user->getProfile() method, you will get the profile as the user sees it.
    // But sometimes, you might want to see the profile as an admin, which depending on your configuration might show more fields.
    if (showViewModeSwitcher() === 'admin') {
        // The getOneAsAdmin method will return the profile as an admin sees it.
        // This includes both the values and the structure of the profile.
        $profileStructure = $client->profile()->getOneAsAdmin($user)->getStructure();
    } else {
        // The user will always return the profile as that user should see it.
        $profileStructure = $user->getProfile()->getStructure();
    }

    showFieldValues($user, $profileStructure);

    Examples::showBackLink();
});

/**
 * Creates a simple interface to switch between user and admin view modes.
 *
 * This function:
 * 1. Checks if there's a 'mode' parameter in the URL (defaulting to 'user' if not present)
 * 2. Shows the current view mode
 * 3. Provides a link to switch to the other mode
 *
 * The view mode affects what profile fields are visible:
 * - User mode: Shows only fields the user has permission to see
 * - Admin mode: Shows additional fields that only administrators can access
 *
 * @return string The current view mode ('user' or 'admin')
 */
function showViewModeSwitcher(): string
{
    $viewMode = in_array($_GET['mode'] ?? '', ['user', 'admin']) ? $_GET['mode'] : 'user';
    echo '<p>You are currently viewing the profile as: ' . $viewMode . '<br>';
    if ($viewMode === 'admin') {
        echo '<a href="' . Examples::getRouteUrl() . '">Switch to user mode</a></p>';
    } else {
        echo '<a href="' . Examples::getRouteUrl() . '?mode=admin">Switch to admin mode</a></p>';
    }
    return $viewMode;
}

/**
 * Displays all profile fields for a user in an organized structure.
 *
 * This function organizes and displays profile fields in two sections:
 * 1. Ungrouped fields (shown first)
 * 2. Grouped fields (shown in their respective groups)
 *
 * The display process:
 * 1. First displays any fields that don't belong to a group
 * 2. Then displays each group as a section with its own fields
 * 3. For empty groups, shows a "No fields in this group" message
 *
 * The function uses getFieldsWithGlobals() which returns:
 * - Fields specific to the current client
 * - Global fields (shared across all clients)
 * This ensures we only show relevant fields for the current context.
 *
 * @param User $user The user whose profile fields should be displayed
 * @param ProfileStructure $structure Contains the definition of all available fields
 * @return void
 */
function showFieldValues(User $user, ProfileStructure $structure): void
{
    echo '<ul>';

    // Iterate all fields that are not in a group (indicated by setting the group filter to false).
    // You will only see fields for the current client or are global (not client specific).
    foreach ($structure->getFieldsWithGlobals(group: false) as $field) {
        showSingleFieldValue($user, $field);
    }

    // Iterate all groups that are either registered for the current client or are global (not client specific).
    foreach ($structure->getGroupsWithGlobals() as $group) {
        echo '<li><strong>' . $group->getDisplayName() . '</strong><ul>';
        $hasItems = false;

        // Iterate all fields that are in the current group.
        foreach ($structure->getFieldsWithGlobals(group: $group) as $field) {
            $hasItems = true;
            showSingleFieldValue($user, $field);
        }

        if (!$hasItems) {
            echo '<li><em>No fields in this group</em></li>';
        }

        echo '</ul></li>';
    }

    echo '</ul>';
}

/**
 * Displays a single profile field's value in a list item format.
 *
 * For each field, this function shows:
 * - The display name (human-readable label)
 * - The full field name (technical identifier)
 * - The field's current value (or "not given" if empty)
 *
 * @param User $user The user whose field value should be displayed
 * @param ProfileField $field The field definition containing name and display properties
 * @return void
 */
function showSingleFieldValue(User $user, ProfileField $field): void
{
    echo '<li>';
    echo $field->getDisplayName() . ' (' . $field->getFullName() . '): ' .
        $user->getProfile()->getAttribute($field->getFullName(), 'not given');
    echo '</li>';
}

/**
 * Creates form fields for editing profile information.
 *
 * This function generates HTML form elements for all editable profile fields:
 * 1. First renders ungrouped fields
 * 2. Then creates fieldset sections for each group of fields
 *
 * Each field is rendered based on its type (text, textarea, date, select)
 * and respects the field's configuration (required, readonly, etc.)
 *
 * @param User $user The user whose profile is being edited
 * @param ProfileStructure $structure Contains all field definitions
 * @return void
 */
function showFieldEditors(User $user, ProfileStructure $structure): void
{
    // Iterate all fields that are not in a group (indicated by setting the group filter to false).
    // You will only see fields for the current client or are global (not client specific).
    foreach ($structure->getFieldsWithGlobals(group: false) as $field) {
        showSingleFieldEditor($user, $field);
    }

    // Iterate all groups that are either registered for the current client or are global (not client specific).
    foreach ($structure->getGroupsWithGlobals() as $group) {
        echo '<fieldset><legend>' . $group->getDisplayName() . '</legend>';
        $hasItems = false;

        // Iterate all fields that are in the current group.
        foreach ($structure->getFieldsWithGlobals(group: $group) as $field) {
            $hasItems = true;
            showSingleFieldEditor($user, $field);
        }

        if (!$hasItems) {
            echo '<em>No fields in this group</em>';
        }

        echo '</fieldset>';
    }
}

/**
 * Creates the appropriate form input element for a single profile field.
 *
 * This function handles different types of input fields:
 * - Readonly fields: Displayed as disabled text inputs
 * - Textarea: For longer text content
 * - Date: Uses the HTML5 date picker
 * - Select: Dropdown with predefined options
 * - Text: Standard text input (default)
 *
 * The function also:
 * - Adds required markers (*) for mandatory fields
 * - Sets the current value of the field
 * - Handles proper field naming for form submission
 *
 * @param User $user The user whose field is being edited
 * @param ProfileField $field The field definition containing type and validation rules
 * @return void
 */
function showSingleFieldEditor(User $user, ProfileField $field): void
{
    echo '<p>';

    try {
        echo '<label for="' . $field->getFullName() . '">' .
            $field->getDisplayName() .
            ($field->isRequired() ? '*' : '') .
            '</label><br>';

        if ($field->isReadOnly()) {
            echo '<input type="text" readonly disabled value="' . $user->getProfile()->getAttribute($field->getFullName(), '') . '">';
            return;
        }

        $required = $field->isRequired() ? ' required' : '';

        if ($field->getInputType() === FieldInputTypes::TEXTAREA->value) {
            echo '<textarea id="' . $field->getFullName() . '" name="' . fullNameToFieldName($field->getFullName()) . '"' . $required . '>';
            echo $user->getProfile()->getAttribute($field->getFullName(), '');
            echo '</textarea>';
            return;
        }

        if ($field->getInputType() === FieldInputTypes::DATE->value) {
            echo '<input type="date" id="' . $field->getFullName() . '" name="' . fullNameToFieldName($field->getFullName()) . '" value="' .
                $user->getProfile()->getAttribute($field->getFullName(), '') . '"' . $required . '>';
            return;
        }

        if ($field->getInputType() === FieldInputTypes::SELECT->value) {
            echo '<select id="' . $field->getFullName() . '" name="' . fullNameToFieldName($field->getFullName()) . '"' . $required . '>';
            foreach (($field->getValidation('options')['options'] ?? []) as $option) {
                echo '<option value="' . $option . '" ' .
                    ($user->getProfile()->getAttribute($field->getFullName(), '') === $option ? 'selected' : '') . '>' .
                    $option .
                    '</option>';
            }
            echo '</select>';
            return;
        }

        echo '<input type="text" id="' . $field->getFullName() . '" name="' . fullNameToFieldName($field->getFullName()) . '" value="' .
            $user->getProfile()->getAttribute($field->getFullName(), '') . '"' . $required . '>';

    } finally {
        echo '</p>';
    }
}

/**
 * Converts a field's full name to a safe format for use in HTML forms.
 *
 * This function:
 * 1. Takes the full field name (which may contain special characters)
 * 2. Encodes it to base64 to preserve all characters
 * 3. URL-encodes the result to make it safe for use in URLs and forms
 *
 * @param string $fullName The original field name
 * @return string The encoded field name
 */
function fullNameToFieldName(string $fullName): string
{
    return urlencode(base64_encode($fullName));
}

/**
 * Converts an encoded field name back to its original form.
 *
 * This function reverses the encoding done by fullNameToFieldName():
 * 1. URL-decodes the field name
 * 2. Decodes the base64 string to get the original field name
 *
 * @param string $fieldName The encoded field name from the form
 * @return string The original field name
 */
function fieldNameToFullName(string $fieldName): string
{
    return base64_decode(urldecode($fieldName));
}
