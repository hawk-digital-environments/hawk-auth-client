<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Keycloak\Query;


use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use Hawk\AuthClient\Exception\FailedToRemoveResourceException;
use Hawk\AuthClient\Resources\Value\Resource;
use Hawk\AuthClient\Util\ErrorResponseMessage;

class RemoveResourceQuery
{
    protected Resource $resource;

    public function __construct(Resource $resource)
    {
        $this->resource = $resource;
    }

    public function execute(ClientInterface $client): void
    {
        try {
            $client->request(
                'DELETE',
                'realms/{realm}/authz/protection/resource_set/' . $this->resource->getId()
            );
        } catch (ClientException $e) {
            throw new FailedToRemoveResourceException(
                (string)new ErrorResponseMessage($e->getResponse()),
                $this->resource,
                previous: $e
            );
        }
    }
}
