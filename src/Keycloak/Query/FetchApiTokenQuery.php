<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Keycloak\Query;


use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Hawk\AuthClient\Exception\ApiTokenRequestFailedException;
use Hawk\AuthClient\Keycloak\Value\ApiToken;
use Hawk\AuthClient\Keycloak\Value\ConnectionConfig;
use Psr\Clock\ClockInterface;

class FetchApiTokenQuery
{
    private ConnectionConfig $config;
    private ClockInterface $clock;

    public function __construct(
        ConnectionConfig $config,
        ClockInterface   $clock
    )
    {
        $this->config = $config;
        $this->clock = $clock;
    }

    public function execute(ClientInterface $client): ApiToken
    {
        try {
            $response = $client->request(
                'POST',
                'realms/{realm}/protocol/openid-connect/token',
                [
                    'form_params' => [
                        'client_id' => $this->config->getClientId(),
                        'client_secret' => $this->config->getClientSecret(),
                        'grant_type' => 'client_credentials',
                        'scope' => 'openid'
                    ],
                ],
            );

            $parsedResponse = json_decode($response->getBody()->getContents(), true);
            $expiresIn = (int)($parsedResponse['expires_in'] ?? 0);

            return new ApiToken(
                $parsedResponse['access_token'],
                $expiresIn > 0 ? $this->clock->now()->add(new \DateInterval('PT' . $expiresIn . 'S')) : $this->clock->now()
            );
        } catch (GuzzleException $e) {
            throw new ApiTokenRequestFailedException($e);
        }
    }
}
