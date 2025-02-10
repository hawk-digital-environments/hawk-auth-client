<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Keycloak\Query;


use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use Hawk\AuthClient\Exception\FailedRequestDueToMissingPermissionsException;
use Hawk\AuthClient\Profiles\Structure\Util\ProfileStructureData;

class FetchProfileStructureQuery
{
    public function execute(ClientInterface $client): ProfileStructureData
    {
        try {
            $response = $client->request(
                'GET',
                'realms/{realm}/hawk/profile/structure'
            );

            $data = json_decode($response->getBody()->getContents(), true);
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 401) {
                throw new FailedRequestDueToMissingPermissionsException(
                    'hawk-view-profile-structure',
                    previous: $e
                );
            }
            throw $e;
        }

        return new ProfileStructureData($data);
    }
}
