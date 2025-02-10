<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Keycloak\Query;


use GuzzleHttp\ClientInterface;
use Hawk\AuthClient\Resources\Value\Resource;
use Hawk\AuthClient\Resources\Value\ResourceScopes;
use Psr\Http\Message\ResponseInterface;

class FetchResourceUserIdStreamQuery extends AbstractChunkedQuery
{
    protected Resource $resource;

    public function __construct(Resource $resource)
    {
        $this->resource = $resource;
    }

    #[\Override] protected function doRequest(ClientInterface $client, int $first, int $max): ResponseInterface
    {
        return $client->request(
            'GET',
            'realms/{realm}/hawk/resources/' . $this->resource->getId() . '/users',
            [
                'query' => [
                    'first' => $first,
                    'max' => $max
                ]
            ]
        );
    }

    #[\Override] protected function dataToItem(mixed $dataItem): array
    {
        return [$dataItem['id'], new ResourceScopes(...$dataItem['scopes'])];
    }

    #[\Override] protected function getCacheKey(): string
    {
        return parent::getCacheKey() . '.' . $this->resource->getId();
    }
}
