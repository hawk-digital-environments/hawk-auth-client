<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Exception;


use Hawk\AuthClient\Resources\Value\Resource;
use Hawk\AuthClient\Users\Value\User;

class FailedToSetUserResourcePermissionException extends \DomainException implements AuthClientExceptionInterface
{
    public function __construct(User $user, Resource $resource, array $scopes, \Throwable|null $previous = null)
    {
        parent::__construct(
            sprintf(
                empty($scopes)
                    ? 'Failed to remove the permissions of user "%s" from the resource with name "%s"'
                    : 'Failed to set the permissions of user "%s" on the resource with name "%s" to scopes "%s"',
                $user->getUsername(),
                $resource->getName(),
                implode('", "', $scopes)
            ),
            previous: $previous
        );
    }
}
