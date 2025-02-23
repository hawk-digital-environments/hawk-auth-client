<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Keycloak\Query;


use GuzzleHttp\ClientInterface;
use Hawk\AuthClient\Users\Value\UserConstraints;
use Hawk\AuthClient\Util\Uuid;
use Psr\Http\Message\ResponseInterface;

class FetchUserIdStreamQuery extends AbstractChunkedQuery
{
    protected array $baseQuery;

    public function __construct(UserConstraints|null $constraints)
    {
        $this->baseQuery = $this->buildBaseQuery($constraints);
    }

    #[\Override] protected function doRequest(ClientInterface $client, int $first, int $max): ResponseInterface
    {
        return $client->request(
            'GET',
            'realms/{realm}/hawk/users',
            [
                'query' => array_merge($this->baseQuery, [
                    'first' => $first,
                    'max' => $max
                ])
            ]
        );
    }

    /**
     * @inheritDoc
     */
    #[\Override] protected function getCacheTtl(): int|null
    {
        if (($this->baseQuery['onlineOnly'] ?? null) === 'true') {
            return 10;
        }

        return null;
    }

    #[\Override] protected function dataToItem(mixed $dataItem): mixed
    {
        return new Uuid($dataItem);
    }

    #[\Override] protected function getCacheKey(): string
    {
        return parent::getCacheKey() . '-' . md5(json_encode($this->baseQuery));
    }

    protected function buildBaseQuery(UserConstraints|null $constraints): array
    {
        $query = [
            'idsOnly' => 'true'
        ];

        if ($constraints === null) {
            return $query;
        }

        if ($constraints->onlyOnline()) {
            $query['onlineOnly'] = 'true';
        }

        $ids = $constraints->getIds();
        if (!empty($ids)) {
            $query['ids'] = implode(',', $ids);
        } else {
            // If there are ids, we don't need to search or filter by attributes
            if ($constraints->getSearch() !== null) {
                $query['search'] = $constraints->getSearch();
            }

            if (!empty($constraints->getAttributes())) {
                $attributeStrings = [];
                foreach ($constraints->getAttributes() as $key => $value) {
                    $attributeStrings[] = $key . ':' . $value;
                }
                $query['attributes'] = implode(',', $attributeStrings);
            }
        }

        return $query;
    }

}
