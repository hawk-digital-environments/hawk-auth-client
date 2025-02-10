<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Keycloak\Query;


use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Hawk\AuthClient\Exception\ConnectionInfoRequestFailedException;
use Hawk\AuthClient\Exception\InvalidUuidException;
use Hawk\AuthClient\Keycloak\Value\ClientUuid;
use Hawk\AuthClient\Keycloak\Value\ConnectionInfo;

class FetchConnectionInfoQuery
{
    public function execute(ClientInterface $client): ConnectionInfo
    {
        try {
            $response = $client->request(
                'GET',
                'realms/{realm}/hawk/connection-info'
            );

            $data = json_decode($response->getBody()->getContents(), true);

            return new ConnectionInfo(
                $data['keycloakVersion'],
                $data['extensionVersion'],
                $data['clientId'],
                new ClientUuid($data['clientUuid'])
            );
        } catch (GuzzleException|InvalidUuidException $e) {
            throw new ConnectionInfoRequestFailedException($e);
        }
    }
}
