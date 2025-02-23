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
    protected bool $asAdminUser;

    public function __construct(ConnectionConfig $config, User $user, $asAdminUser)
    {
        $this->config = $config;
        $this->user = $user;
        $this->asAdminUser = $asAdminUser;
    }

    public function execute(ClientInterface $client): UserProfile
    {
        $response = $client->request(
            'GET',
            'realms/{realm}/hawk/profile/' . $this->user->getId(),
            [
                'query' => [
                    'mode' => $this->asAdminUser ? 'admin' : 'user'
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
