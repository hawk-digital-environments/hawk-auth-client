<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Layers;


use Hawk\AuthClient\Profiles\ProfileUpdater;
use Hawk\AuthClient\Profiles\Structure\ProfileStructureBuilder;
use Hawk\AuthClient\Profiles\Value\UserProfile;
use Hawk\AuthClient\Users\Value\User;

interface ProfileLayerInterface
{
    /**
     * Keycloak provides a powerful way to define a user profile structure.
     * This method returns a {@see ProfileStructureBuilder} that can be used to declare the profile used in your app.
     * Note, that your client requires the "hawk-manage-profile-structure" service-client-role to define the profile structure.
     *
     * Because, this library was build with multiple clients in mind, that might have different profile structures,
     * everything you define is by default "namespaced" for your client. This means, that you can define a field "name"
     * and another client can also define a field "name" without any conflicts.
     *
     * There is also the option to access the profile structure of other clients, by setting the "clientId" parameter
     * in any of the methods of the {@see ProfileStructureBuilder}.
     *
     * Example usage:
     * ```php
     * $client = new AuthClient();
     *
     * $structure = $client->profile()->define();
     *
     * $personalGroup = $structure->getGroup('personal');
     * $personalGroup->setDisplayName('Personal Information');
     *
     * $nameField = $structure->getField('name');
     * $nameField->setDisplayName('Full Name');
     * $nameField->setGroup($personalGroup);
     *
     * // While this is possible, I would not recommend it, because it might lead to conflicts!
     * $otherAppsField = $structure->getField('name', 'other-app');
     * $otherAppsField->setDisplayName('Name in other app');
     *
     * // Make sure to save the structure so it gets persisted
     * $structure->save();
     * ```
     * @return ProfileStructureBuilder
     */
    public function define(): ProfileStructureBuilder;

    /**
     * By default, your client is only allowed to read the profile data of a user.
     * You can normally do this by accessing the {@see User::getProfile()} method on any user reference.
     * To update the data stored in a users profile your client requires the "hawk-manage-profile-data" service-client-role.
     *
     * This method returns a {@see ProfileUpdater} that can be used to update the profile data of a user.
     *
     * Example usage:
     * ```php
     * $client = new AuthClient();
     *
     * $user = $client->users()->getOne('user-id');
     * print_r($user->getProfile()->jsonSerialize());
     *
     * $updater = $client->profile()->update($user);
     * $updater->setFirstName('John')
     *      ->setLastName('Doe')
     *      ->set('customField', 'value')
     *      // Ensure to save the changes
     *      ->save();
     * ```
     * A word of caution: After calling the {@see ProfileUpdater::save()} method, the changes are immediately persisted,
     * however existing profile instances are not updated. This means you should always use $user->getProfile() to get
     * the latest profile data.
     *
     * @param User $user The user for which to update the profile data.
     * @param bool|null $asAdmin By default, (null|false), the profile is updated as the current user.
     *                           If set to true, the profile is updated as the admin user.
     *                           This determines the range of changeable and required fields.
     * @return ProfileUpdater
     */
    public function update(User $user, bool|null $asAdmin = null): ProfileUpdater;

    /**
     * This method allows access to the profile of a user.
     * But while {@see User::getProfile()} returns the profile data and structure AS THAT user, this method returns the
     * profile structure AS AN ADMIN. This means, there might be different visible fields and required fields.
     * This method is useful if you want to build a form for an admin user to edit the profile of another user.
     *
     * @param User $user
     * @return UserProfile
     * @see User::getProfile() to get the profile structure as the user.
     * @see ProfileLayerInterface::update() also allows you to update the profile data as an admin user.
     */
    public function getOneAsAdmin(User $user): UserProfile;
}
