<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Keycloak\Query;


use GuzzleHttp\ClientInterface;
use Hawk\AuthClient\Keycloak\Value\ConnectionConfig;
use Hawk\AuthClient\Profiles\Value\UserProfile;
use Hawk\AuthClient\Users\Value\User;

class FetchProfileDataQuery
{
    protected User $user;
    protected ConnectionConfig $config;

    public function __construct(ConnectionConfig $config, User $user)
    {
        $this->config = $config;
        $this->user = $user;
    }

    public function execute(ClientInterface $client): UserProfile
    {
        $response = $client->request(
            'GET',
            'admin/realms/{realm}/users/' . $this->user->getId(),
            [
                'query' => [
                    'userProfileMetadata' => "true"
                ]
            ]
        );

        $data = json_decode($response->getBody()->getContents(), true);

        $additionalData = $data;
        unset(
            $additionalData['firstName'],
            $additionalData['lastName'],
            $additionalData['email'],
            $additionalData['attributes'],
            $additionalData['userProfileMetadata']
        );

        return new UserProfile(
            $this->config,
            $this->user->getUsername(),
            $data['firstName'],
            $data['lastName'],
            $data['email'],
            $data['attributes'] ?? [],
            $data['userProfileMetadata'],
            $additionalData
        );
    }
}
