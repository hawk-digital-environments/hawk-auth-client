<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Keycloak\Query;


use GuzzleHttp\ClientInterface;
use Hawk\AuthClient\Roles\Value\Role;
use Hawk\AuthClient\Roles\Value\RoleList;
use Hawk\AuthClient\Util\Uuid;

class FetchRolesQuery
{
    public function execute(ClientInterface $client): RoleList
    {
        $response = $client->request(
            'GET',
            'realms/{realm}/hawk/roles',
            [
                'query' => [
                    'q' => '',
                    'max' => 1000
                ]
            ]
        );

        $data = json_decode($response->getBody()->getContents(), true);

        $roles = [];
        foreach ($data as $role) {
            $roles[] = new Role(
                new Uuid($role['id']),
                $role['name'],
                (bool)$role['clientRole'],
                empty($role['description']) ? null : $role['description'],
                (array)($role['attributes'] ?? [])
            );
        }

        return new RoleList(...$roles);
    }
}
