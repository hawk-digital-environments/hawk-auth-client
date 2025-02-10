<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Keycloak\Query;


use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;

class FetchGroupMemberIdStreamQuery extends AbstractChunkedQuery
{
    private string $groupId;

    public function __construct(string $groupId)
    {
        $this->groupId = $groupId;
    }

    #[\Override] protected function doRequest(ClientInterface $client, int $first, int $max): ResponseInterface
    {
        return $client->request(
            'GET',
            'admin/realms/{realm}/groups/' . $this->groupId . '/members',
            [
                'query' => [
                    'briefRepresentation' => 'true',
                    'first' => $first,
                    'max' => $max
                ]
            ]
        );
    }

    #[\Override] protected function dataToItem(mixed $dataItem): mixed
    {
        return $dataItem['id'];
    }


    #[\Override] protected function getCacheKey(): string
    {
        return parent::getCacheKey() . '-' . $this->groupId;
    }
}
