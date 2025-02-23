<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Users\Value;


use Hawk\AuthClient\Groups\Value\Group;
use Hawk\AuthClient\Groups\Value\GroupList;
use Hawk\AuthClient\Groups\Value\GroupReferenceList;
use Hawk\AuthClient\Profiles\Value\UserProfile;
use Hawk\AuthClient\Roles\Value\Role;
use Hawk\AuthClient\Roles\Value\RoleList;
use Hawk\AuthClient\Roles\Value\RoleReferenceList;
use Hawk\AuthClient\Util\Uuid;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class User implements ResourceOwnerInterface, \JsonSerializable
{
    public const string ARRAY_KEY_ID = 'id';
    public const string ARRAY_KEY_USERNAME = 'username';
    public const string ARRAY_KEY_CLAIMS = 'claims';
    public const string ARRAY_KEY_ROLES = 'roles';
    public const string ARRAY_KEY_GROUPS = 'groups';

    protected Uuid $id;
    protected string $username;
    protected UserClaims $claims;
    protected RoleReferenceList $roleReferenceList;
    protected RoleList|null $roles = null;
    protected GroupReferenceList $groupReferenceList;
    protected GroupList|null $groups = null;
    protected UserProfile|null $profile = null;
    protected UserContext $context;

    public function __construct(
        Uuid $id,
        string             $username,
        UserClaims         $claims,
        RoleReferenceList  $roleReferenceList,
        GroupReferenceList $groupReferenceList,
        UserContext        $context
    )
    {
        $this->id = $id;
        $this->username = $username;
        $this->claims = $claims;
        $this->roleReferenceList = $roleReferenceList;
        $this->groupReferenceList = $groupReferenceList;
        $this->context = $context;
    }

    /**
     * @inheritDoc
     */
    public function getId(): Uuid
    {
        return $this->id;
    }

    /**
     * Returns the unique username of the user.
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * Returns the list of claims of the user.
     * Claims are additional information about the user.
     * They are provided by keycloak through "Client scopes" and "Protocol mappers".
     * Claims can contain any data configure for your realm, as long as the "Add to userinfo" switch is enabled.
     *
     * @return UserClaims
     * @see https://www.keycloak.org/docs/latest/server_admin/index.html#_client_scopes
     */
    public function getClaims(): UserClaims
    {
        return $this->claims;
    }

    /**
     * Returns the list of roles of the user.
     * This is a list of {@see Role} entities! This will potentially require additional api requests to get the role metadata from keycloak,
     * if you only want to get the role names, use {@see getRoleReferences()} instead.
     * This list contains all roles the user has assigned, both from the realm and from the client.
     *
     * @return RoleList
     * @see getRoleReferences() to get only the role names.
     */
    public function getRoles(): RoleList
    {
        if ($this->roles === null) {
            $this->roles = $this->context->getRoles($this->roleReferenceList);
        }

        return $this->roles;
    }

    /**
     * Similar to {@see getRoles()}, but can be used as a lightweight alternative to get the role names without loading the full role metadata.
     * @return RoleReferenceList
     */
    public function getRoleReferences(): RoleReferenceList
    {
        return $this->roleReferenceList;
    }

    /**
     * Returns the list of groups of the user.
     * This is a list of {@see Group} entities! This will potentially require additional api requests to get the group metadata from keycloak,
     * if you only want to get the group names, use {@see getGroupReferences()} instead.
     * This is a flat list of all groups the user is a member of, including nested groups.
     *
     * @return GroupList
     */
    public function getGroups(): GroupList
    {
        if ($this->groups === null) {
            $this->groups = $this->context->getGroups($this->groupReferenceList);
        }

        return $this->groups;
    }

    /**
     * Similar to {@see getGroups()}, but can be used as a lightweight alternative to get the group names without loading the full group metadata.
     * @return GroupReferenceList
     */
    public function getGroupReferences(): GroupReferenceList
    {
        return $this->groupReferenceList;
    }

    /**
     *
     * @return UserProfile
     */
    public function getProfile(): UserProfile
    {
        if ($this->profile === null) {
            $this->profile = $this->context->getProfile($this);
        }

        return $this->profile;
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function toArray(): array
    {
        return [
            self::ARRAY_KEY_ID => (string)$this->id,
            self::ARRAY_KEY_USERNAME => $this->username,
            self::ARRAY_KEY_CLAIMS => $this->claims->jsonSerialize(),
            self::ARRAY_KEY_ROLES => array_map('strval', $this->roleReferenceList->jsonSerialize()),
            self::ARRAY_KEY_GROUPS => array_map('strval', $this->groupReferenceList->jsonSerialize())
        ];
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
