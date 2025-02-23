<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Users\Value;


use Hawk\AuthClient\Groups\Value\GroupReferenceList;
use Hawk\AuthClient\Resources\Value\ResourceScopes;
use Hawk\AuthClient\Roles\Value\RoleReferenceList;
use Hawk\AuthClient\Util\Uuid;

class ResourceUser extends User
{
    protected ResourceScopes $scopes;

    public function __construct(
        Uuid $id,
        string             $username,
        UserClaims         $claims,
        RoleReferenceList  $roleReferenceList,
        GroupReferenceList $groupReferenceList,
        UserContext        $context,
        ResourceScopes     $scopes
    )
    {
        parent::__construct($id, $username, $claims, $roleReferenceList, $groupReferenceList, $context);
        $this->scopes = $scopes;
    }

    /**
     * Returns the scopes associated with this user on the resource
     * @return ResourceScopes
     */
    public function getScopes(): ResourceScopes
    {
        return $this->scopes;
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function toArray(): array
    {
        return array_merge(
            parent::toArray(),
            [
                'scopes' => $this->scopes->jsonSerialize()
            ]
        );
    }

    public static function fromUserAndScopes(User $user, ResourceScopes $scopes): static
    {
        return new static(
            $user->id,
            $user->username,
            $user->claims,
            $user->roleReferenceList,
            $user->groupReferenceList,
            $user->context,
            $scopes
        );
    }
}
