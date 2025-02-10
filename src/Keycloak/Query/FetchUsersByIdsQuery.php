<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Keycloak\Query;


use GuzzleHttp\ClientInterface;
use Hawk\AuthClient\Users\UserFactory;

class FetchUsersByIdsQuery
{
    private UserFactory $userFactory;
    private array $userIds;

    public function __construct(
        UserFactory $userFactory,
        string      ...$userIds
    )
    {
        $this->userFactory = $userFactory;
        $this->userIds = $userIds;
    }

    public function execute(ClientInterface $client): \Generator
    {
        $response = $client->request(
            'GET',
            'realms/{realm}/hawk/users',
            [
                'query' => [
                    'ids' => implode(',', $this->userIds),
                    'max' => count($this->userIds)
                ]
            ]
        );

        foreach (json_decode($response->getBody()->getContents(), true) as $userData) {
            $user = $this->userFactory->makeUserFromKeycloakData($userData);
            yield $user->getId() => $user;
        }
    }
}
