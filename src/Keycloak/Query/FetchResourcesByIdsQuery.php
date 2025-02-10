<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Keycloak\Query;


use GuzzleHttp\ClientInterface;
use Hawk\AuthClient\Resources\ResourceFactory;

class FetchResourcesByIdsQuery
{
    protected ResourceFactory $resourceFactory;
    protected array $ids;

    public function __construct(
        ResourceFactory $resourceFactory,
        string ...$ids)
    {
        $this->resourceFactory = $resourceFactory;
        $this->ids = $ids;
    }

    public function execute(ClientInterface $client): iterable
    {
        $response = $client->request(
            'GET',
            'realms/{realm}/hawk/resources',
            [
                'query' => [
                    'ids' => implode(',', $this->ids),
                    'max' => count($this->ids)
                ]
            ]
        );

        foreach (json_decode($response->getBody()->getContents(), true) as $resourceData) {
            $resource = $this->resourceFactory->makeResourceFromKeycloakData($resourceData);
            yield $resource->getId() => $resource;
        }
    }
}
