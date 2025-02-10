<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Keycloak\Query;


use GuzzleHttp\ClientInterface;
use Hawk\AuthClient\Resources\Value\Resource;
use Hawk\AuthClient\Resources\Value\ResourceScopes;
use Hawk\AuthClient\Users\Value\User;

class FetchResourceScopesGrantedToUserQuery
{
    protected Resource $resource;
    protected User $user;

    public function __construct(Resource $resource, User $user)
    {
        $this->resource = $resource;
        $this->user = $user;
    }

    public function execute(ClientInterface $client): ResourceScopes|null
    {
        $response = $client->request(
            'POST',
            'admin/realms/{realm}/clients/{clientUuid}/authz/resource-server/permission/evaluate',
            [
                'json' => [
                    'roleIds' => [],
                    'userId' => $this->user->getId(),
                    'entitlements' => true,
                    'context' => [
                        'attributes' => (object)[]
                    ],
                    'resources' => [[
                        '_id' => $this->resource->getId()
                    ]]
                ]
            ]
        );

        $data = json_decode($response->getBody()->getContents(), true);
        $permissions = $data['rpt']['authorization']['permissions'] ?? [];
        if (empty($permissions)) {
            return null;
        }

        $scopes = array_merge(array_column($permissions, 'scopes'));
        return empty($scopes) ? null : new ResourceScopes(...array_unique(array_merge(...$scopes)));
    }
}
