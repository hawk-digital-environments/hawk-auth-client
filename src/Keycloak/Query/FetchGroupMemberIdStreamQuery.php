<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Keycloak\Query;


use GuzzleHttp\ClientInterface;
use Hawk\AuthClient\Util\Uuid;
use Psr\Http\Message\ResponseInterface;

class FetchGroupMemberIdStreamQuery extends AbstractChunkedQuery
{
    private Uuid $groupId;

    public function __construct(Uuid $groupId)
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
        return new Uuid($dataItem['id']);
    }


    #[\Override] protected function getCacheKey(): string
    {
        return parent::getCacheKey() . '-' . $this->groupId;
    }
}
