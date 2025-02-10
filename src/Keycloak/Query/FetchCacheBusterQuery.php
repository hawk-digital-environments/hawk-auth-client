<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Keycloak\Query;


use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Hawk\AuthClient\Keycloak\Value\CacheBuster;
use Psr\Clock\ClockInterface;

class FetchCacheBusterQuery
{
    private ClockInterface $clock;

    public function __construct(ClockInterface $clock)
    {
        $this->clock = $clock;
    }

    public function execute(ClientInterface $client): CacheBuster
    {
        try {
            $response = $client->request(
                'GET',
                'realms/{realm}/hawk/cache-buster',
            );

            $buster = $response->getBody()->getContents();
        } catch (GuzzleException) {
            $buster = null;
        }

        if (empty($buster)) {
            $buster = $this->clock->now()->getTimestamp();
        }

        return new CacheBuster((string)$buster);
    }
}
