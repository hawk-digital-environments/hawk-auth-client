<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Profiles\Value;


use Hawk\AuthClient\Keycloak\Value\ConnectionConfig;
use Hawk\AuthClient\Profiles\Structure\ProfileStructure;
use Hawk\AuthClient\Profiles\Structure\Util\ProfilePrefixTrait;
use Hawk\AuthClient\Profiles\Structure\Util\ProfileStructureData;
use Traversable;

class UserProfile implements \JsonSerializable, \IteratorAggregate
{
    use ProfilePrefixTrait;

    public const string ATTRIBUTE_USERNAME = 'username';
    public const string ATTRIBUTE_FIRST_NAME = 'firstName';
    public const string ATTRIBUTE_LAST_NAME = 'lastName';
    public const string ATTRIBUTE_EMAIL = 'email';
    public const string ATTRIBUTE_EMAIL_VERIFIED = 'emailVerified';

    public const array ROOT_LEVEL_ATTRIBUTES = [
        self::ATTRIBUTE_USERNAME,
        self::ATTRIBUTE_FIRST_NAME,
        self::ATTRIBUTE_LAST_NAME,
        self::ATTRIBUTE_EMAIL,
        self::ATTRIBUTE_EMAIL_VERIFIED
    ];

    protected string $username;
    readonly protected string $firstName;
    readonly protected string $lastName;
    readonly protected string $email;
    readonly protected array $attributes;
    readonly protected array $rawStructure;
    readonly protected ProfileStructure $structure;
    readonly protected array $additionalData;

    public function __construct(
        ConnectionConfig $config,
        string           $username,
        string           $firstName,
        string           $lastName,
        string           $email,
        array            $attributes,
        array            $structure,
        array            $additionalData = []
    )
    {
        $this->config = $config;
        $this->username = $username;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->attributes = $attributes;
        $this->rawStructure = $structure;
        $this->structure = new ProfileStructure($this->config, new ProfileStructureData($this->rawStructure));
        $this->additionalData = $additionalData;
    }

    /**
     * Returns the username of the user.
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * Returns the first name of the user.
     * Returns an empty string if the first name is not set.
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * Returns the last name of the user.
     * Returns an empty string if the last name is not set.
     * @return string
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * Returns the email of the user. Might be empty if the user has no email.
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Returns any attribute of the user profile. This includes both the "default" attributes (username, first name, last name, email)
     * and any custom attributes, provided by keycloak or {@see ProfileLayerInterface::define()}.
     *
     * Note that, as stated in {@see ProfileLayerInterface::define} the attributes you define in the profile layer
     * are automatically prefixed with the client id. This means that if you define an attribute "test-attribute" for the client "test-client",
     * you have to access it with the key "test-attribute" when you are accessing it in the test-client. This has the
     * advantage that you can define the same attribute for different clients without any conflicts.
     *
     * You can also access the default attributes (username, first name, last name, email) by setting the clientId to false.
     *
     * To access attributes defined for other clients, you can set the clientId to the client id of the client you want to access.
     *
     * Note, that attributes are returned as their actual value (if they are not marked as "multivalued")
     * and as an array of values if they are marked as "multivalued".
     *
     * @param string $key The key of the attribute,
     * @param mixed|null $default The default value that should be returned if the attribute is not set.
     * @param false|string|\Stringable|null $clientId The client id of the client you want to access the attribute for.
     *                                                If set to false, the default attributes (username, first name, last name, email) are returned.
     *                                                If set to null, the attribute is accessed for the current client.
     *                                                The client id of the client you want to access the attribute for.
     * @return mixed
     */
    public function getAttribute(string $key, mixed $default = null, false|null|string|\Stringable $clientId = null): mixed
    {
        if ($clientId === false) {
            if ($key === self::ATTRIBUTE_USERNAME) {
                return $this->getUsername();
            }
            if ($key === self::ATTRIBUTE_FIRST_NAME) {
                return $this->getFirstName();
            }
            if ($key === self::ATTRIBUTE_LAST_NAME) {
                return $this->getLastName();
            }
            if ($key === self::ATTRIBUTE_EMAIL) {
                return $this->getEmail();
            }
            if ($key === self::ATTRIBUTE_EMAIL_VERIFIED) {
                return $this->additionalData[self::ATTRIBUTE_EMAIL_VERIFIED] ?? false;
            }
        }

        $fullName = $this->getFullName($key, $clientId);

        return $this->unpackAttribute(
            $fullName,
            $this->attributes[$fullName] ?? null
        ) ?? $default;
    }

    /**
     * Returns the raw list of all attributes of all clients in the user profile.
     * This does NOT include the default attributes (username, first name, last name, email)!
     * To get an iterable of all attributes, use {@see getIterator()}.
     * Note, that this method does return the attributes in the Keycloak format, which means everything is wrapped in an array!
     *
     * @return array
     */
    public function getRawAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Returns the structure of the fields and groups defined for the user profile.
     * Please note, that based on the user, client and the permissions of your client, the structure might differ!
     * If you want to build a form for your user, always use the structure provided by this method!
     *
     * @return ProfileStructure
     */
    public function getStructure(): ProfileStructure
    {
        return $this->structure;
    }

    /**
     * Returns the additional data of the user profile.
     * These are fields that are part of the Keycloak user profile but do not seem relevant for most use cases.
     *
     * @return array
     */
    public function getAdditionalData(): array
    {
        return $this->additionalData;
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function getIterator(false|null|string|\Stringable $clientId = null): Traversable
    {
        if ($clientId === false) {
            yield self::ATTRIBUTE_USERNAME => $this->getUserName();
            yield self::ATTRIBUTE_FIRST_NAME => $this->getFirstName();
            yield self::ATTRIBUTE_LAST_NAME => $this->getLastName();
            yield self::ATTRIBUTE_EMAIL => $this->getEmail();
            yield self::ATTRIBUTE_EMAIL_VERIFIED => $this->additionalData[self::ATTRIBUTE_EMAIL_VERIFIED] ?? false;
        }

        foreach ($this->attributes as $key => $value) {
            if ($this->belongsTo($key, $clientId)) {
                yield $this->getLocalName($key, $clientId) => $this->unpackAttribute($key, $value);
            }
        }
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function jsonSerialize(): array
    {
        return [
            'username' => $this->username,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'email' => $this->email,
            'attributes' => $this->attributes,
            'structure' => $this->rawStructure,
            'additionalData' => $this->additionalData
        ];
    }

    public static function fromArray(ConnectionConfig $config, array $data): static
    {
        return new static(
            $config,
            $data['username'],
            $data['firstName'],
            $data['lastName'],
            $data['email'],
            $data['attributes'],
            $data['structure'],
            $data['additionalData'] ?? []
        );
    }

    protected function unpackAttribute(string $fullName, mixed $value): mixed
    {
        $field = $this->getStructure()->getField($fullName);
        if ($field && $field->isMultiValued()) {
            return $value;
        }

        return is_array($value) ? reset($value) : $value;
    }
}
