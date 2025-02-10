<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Keycloak\Query;


use GuzzleHttp\ClientInterface;
use Hawk\AuthClient\Resources\ResourceFactory;
use Hawk\AuthClient\Resources\Value\Resource;

class FetchResourceByNameQuery
{
    protected ResourceFactory $resourceFactory;
    private string $resourceName;

    public function __construct(ResourceFactory $resourceFactory, string $resourceName)
    {
        $this->resourceName = $resourceName;
        $this->resourceFactory = $resourceFactory;
    }

    public function execute(ClientInterface $client): Resource|null
    {
        $response = $client->request(
            'GET',
            'realms/{realm}/authz/protection/resource_set',
            [
                'query' => [
                    'name' => $this->resourceName,
                    'exactName' => "true",
                    'deep' => "true"
                ]
            ]
        );

        $data = json_decode($response->getBody()->getContents(), true);

        if(!is_array($data[0] ?? null)){
            return null;
        }

        return $this->resourceFactory->makeResourceFromKeycloakData($data[0]);
    }
}
