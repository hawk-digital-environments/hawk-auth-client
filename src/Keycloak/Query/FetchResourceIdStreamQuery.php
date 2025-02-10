<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Keycloak\Query;


use GuzzleHttp\ClientInterface;
use Hawk\AuthClient\Resources\Value\ResourceConstraints;
use Psr\Http\Message\ResponseInterface;

class FetchResourceIdStreamQuery extends AbstractChunkedQuery
{
    protected array $baseQuery;

    public function __construct(ResourceConstraints|null $constraints)
    {
        $this->baseQuery = $this->buildBaseQuery($constraints);
    }

    #[\Override] protected function doRequest(ClientInterface $client, int $first, int $max): ResponseInterface
    {
        return $client->request(
            'GET',
            'realms/{realm}/hawk/resources',
            [
                'query' => array_merge($this->baseQuery, [
                    'first' => $first,
                    'max' => $max
                ])
            ]
        );
    }

    #[\Override] protected function dataToItem(mixed $dataItem): mixed
    {
        return $dataItem;
    }

    #[\Override] protected function getCacheKey(): string
    {
        return parent::getCacheKey() . '-' . md5(json_encode($this->baseQuery));
    }

    protected function buildBaseQuery(ResourceConstraints|null $constraints): array
    {
        $query = [
            'idsOnly' => 'true'
        ];

        if ($constraints === null) {
            return $query;
        }

        $ids = $constraints->getIds();
        if (!empty($ids)) {
            $query['ids'] = implode(',', $ids);
        } else {
            // If there are ids, we don't need to add any other filters
            if ($constraints->getName() !== null) {
                $query['name'] = $constraints->getName();
                if ($constraints->isExactNames()) {
                    $query['exactNames'] = 'true';
                }
            }

            if ($constraints->getUri() !== null) {
                $query['uri'] = $constraints->getUri();
            }

            if ($constraints->getType() !== null) {
                $query['type'] = $constraints->getType();
            }
        }

        // Those filters can be used together
        if ($constraints->getSharedWith() !== null) {
            $query['sharedWith'] = $constraints->getSharedWith();
        }

        // Those filters can be used together
        if ($constraints->getOwner() !== null && (empty($ids) || $constraints->isSharedOnly())) {
            $query['owner'] = $constraints->getOwner();
            if ($constraints->isSharedOnly()) {
                $query['sharedOnly'] = 'true';
            }
        }

        return $query;
    }
}
