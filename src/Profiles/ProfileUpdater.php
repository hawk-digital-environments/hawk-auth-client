<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Profiles;


use Hawk\AuthClient\Keycloak\Value\ConnectionConfig;
use Hawk\AuthClient\Layers\ProfileLayerInterface;
use Hawk\AuthClient\Profiles\Structure\Util\ProfilePrefixTrait;
use Hawk\AuthClient\Profiles\Value\UserProfile;
use Hawk\AuthClient\Users\Value\User;

class ProfileUpdater
{
    use ProfilePrefixTrait;

    protected User $user;
    protected ProfileStorage $storage;
    protected array $changeSet = [];
    protected bool $asAdminUser;

    public function __construct(
        User             $user,
        ConnectionConfig $config,
        ProfileStorage $storage,
        bool           $asAdminUser
    )
    {
        $this->user = $user;
        $this->config = $config;
        $this->storage = $storage;
        $this->asAdminUser = $asAdminUser;
    }

    /**
     * Sets the username of the user to the given value.
     * Be careful with this method, as changing the username can have side effects!
     *
     * @param string $username
     * @return $this
     */
    public function setUsername(string $username): static
    {
        $this->changeSet[UserProfile::ATTRIBUTE_USERNAME] = $username;
        return $this;
    }

    /**
     * Sets the first name of the user to the given value.
     *
     * @param string $firstName
     * @return $this
     */
    public function setFirstName(string $firstName): static
    {
        $this->changeSet[UserProfile::ATTRIBUTE_FIRST_NAME] = $firstName;
        return $this;
    }

    /**
     * Sets the last name of the user to the given value.
     *
     * @param string $lastName
     * @return $this
     */
    public function setLastName(string $lastName): static
    {
        $this->changeSet[UserProfile::ATTRIBUTE_LAST_NAME] = $lastName;
        return $this;
    }

    /**
     * Sets the email of the user to the given value.
     *
     * @param string $email The new email address.
     * @param bool|null $verified Whether the email address has been verified.
     * @return $this
     */
    public function setEmail(string $email, bool|null $verified = null): static
    {
        $this->changeSet[UserProfile::ATTRIBUTE_EMAIL] = $email;

        if ($verified !== null) {
            $this->changeSet[UserProfile::ATTRIBUTE_EMAIL_VERIFIED] = $verified;
        }

        return $this;
    }

    /**
     * Sets any custom attribute of the user to the given value.
     * By default, the attribute is set to the client's namespace {@see ProfileLayerInterface::update} for further details on this matter.
     *
     * @param string $key The name of the attribute.
     * @param mixed $value The value of the attribute.
     * @param false|string|\Stringable|null $clientId The client ID to set the attribute for.
     *                                                null (default) - prefixed with the current client ID
     *                                                false - global attribute (no prefix)
     *                                                string - prefixed with the given client ID (BE CAREFUL!)
     * @return $this
     */
    public function set(string $key, mixed $value, false|null|string|\Stringable $clientId = null): static
    {
        $value = is_array($value) ? $value : [$value];
        $this->changeSet[$this->getFullName($key, $clientId)] = $value;
        return $this;
    }

    /**
     * Persists the changes to the user profile.
     * @return $this
     */
    public function save(): static
    {
        $profile = $this->storage->getProfileOfUser($this->user, $this->asAdminUser);
        $modified = [];
        foreach ($this->changeSet as $key => $value) {
            $currentValue = $profile->getAttribute($key, null, false);
            $currentValue = is_array($currentValue) ? $currentValue : [$currentValue];
            if ($currentValue !== $value) {
                $modified[$key] = $value;
            }
        }

        $this->storage->updateProfile($this->user, $modified, $this->asAdminUser);
        return $this;
    }
}
