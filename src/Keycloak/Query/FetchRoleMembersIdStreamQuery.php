<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Keycloak\Query;


use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;

class FetchRoleMembersIdStreamQuery extends AbstractChunkedQuery
{
    private string $roleId;

    public function __construct(string $roleId)
    {
        $this->roleId = $roleId;
    }

    #[\Override] protected function doRequest(ClientInterface $client, int $first, int $max): ResponseInterface
    {
        return $client->request(
            'GET',
            'realms/{realm}/hawk/roles/' . $this->roleId . '/members',
            [
                'query' => [
                    'first' => $first,
                    'max' => $max
                ]
            ]
        );
    }

    #[\Override] protected function dataToItem(mixed $dataItem): mixed
    {
        return $dataItem;
    }

    #[\Override] protected function getCacheKey(): string
    {
        return parent::getCacheKey() . '-' . $this->roleId;
    }
}
