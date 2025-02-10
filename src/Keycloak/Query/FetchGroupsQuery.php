<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Keycloak\Query;


use GuzzleHttp\ClientInterface;
use Hawk\AuthClient\Groups\Value\GroupList;

class FetchGroupsQuery
{
    public function execute(ClientInterface $client): GroupList
    {
        $response = $client->request(
            'GET',
            'admin/realms/{realm}/groups',
            [
                'query' => [
                    'q' => '',
                    'max' => 1000
                ]
            ]
        );

        $data = json_decode($response->getBody()->getContents(), true);

        return GroupList::fromScalarList(...$data);
    }
}
