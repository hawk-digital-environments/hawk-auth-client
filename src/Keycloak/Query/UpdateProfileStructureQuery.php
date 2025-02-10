<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Keycloak\Query;


use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Hawk\AuthClient\Exception\FailedRequestDueToMissingPermissionsException;
use Hawk\AuthClient\Exception\ProfileDefinitionFailedException;
use Hawk\AuthClient\Profiles\Structure\Util\ProfileStructureData;
use Hawk\AuthClient\Util\ErrorResponseMessage;

class UpdateProfileStructureQuery
{
    protected ProfileStructureData $profileStructureData;

    public function __construct(ProfileStructureData $profileStructureData)
    {
        $this->profileStructureData = $profileStructureData;
    }

    public function execute(ClientInterface $client): void
    {
        try {
            $client->request(
                'PUT',
                'realms/{realm}/hawk/profile/structure',
                [
                    'json' => $this->profileStructureData->jsonSerialize()
                ]
            );
        } catch (ClientException|RequestException $e) {
            if ($e->getResponse()->getStatusCode() === 401) {
                throw new FailedRequestDueToMissingPermissionsException(
                    'hawk-manage-profile-structure',
                    previous: $e
                );
            }

            throw new ProfileDefinitionFailedException(
                (string)new ErrorResponseMessage($e->getResponse()),
                previous: $e
            );
        }
    }
}
