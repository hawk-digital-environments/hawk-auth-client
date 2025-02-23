<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Keycloak\Query;


use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Hawk\AuthClient\Exception\ConnectionInfoRequestFailedException;
use Hawk\AuthClient\Exception\InvalidUuidException;
use Hawk\AuthClient\Keycloak\Value\ConnectionInfo;
use Hawk\AuthClient\Util\Uuid;

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
                new Uuid($data['clientUuid']),
                new Uuid($data['clientServiceAccountUuid'])
            );
        } catch (GuzzleException|InvalidUuidException $e) {
            throw new ConnectionInfoRequestFailedException($e);
        }
    }
}
