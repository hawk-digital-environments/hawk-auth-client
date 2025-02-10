<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Keycloak\Query;


use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use Hawk\AuthClient\Exception\FailedRequestDueToMissingPermissionsException;
use Hawk\AuthClient\Exception\FailedToSetUserResourcePermissionException;
use Hawk\AuthClient\Resources\Value\Resource;
use Hawk\AuthClient\Users\Value\User;

class SetResourceUserPermissionsQuery
{
    protected Resource $resource;
    protected User $user;
    protected array $scopes;

    public function __construct(
        Resource   $resource,
        User       $user,
        array|null $scopes
    )
    {
        $this->resource = $resource;
        $this->user = $user;
        $this->scopes = $scopes ?? [];
    }

    public function execute(ClientInterface $client): void
    {
        try {
            $client->request(
                'PUT',
                'realms/{realm}/hawk/resources/'. $this->resource->getId() . '/users/' . $this->user->getId(),
                [
                    'json' => [
                        'scopes' => $this->scopes
                    ]
                ]
            );
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 401) {
                throw new FailedRequestDueToMissingPermissionsException(
                    'hawk-manage-resource-permissions',
                    previous: $e
                );
            }

            throw new FailedToSetUserResourcePermissionException(
                $this->user,
                $this->resource,
                $this->scopes,
                previous: $e
            );
        }
    }
}
