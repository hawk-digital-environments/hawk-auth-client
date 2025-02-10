<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Keycloak\Query;


use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use Hawk\AuthClient\Keycloak\ApiTokenStorage;
use Hawk\AuthClient\Keycloak\ConnectionInfoStorage;
use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Keycloak\Value\ApiToken;
use Hawk\AuthClient\Keycloak\Value\ClientUuid;
use Hawk\AuthClient\Keycloak\Value\ConnectionConfig;
use Hawk\AuthClient\Keycloak\Value\ConnectionInfo;
use Hawk\AuthClient\Resources\ResourceFactory;
use Hawk\AuthClient\Tests\TestUtils\PartialMockWithConstructorArgsTrait;
use Hawk\AuthClient\Users\UserFactory;
use Hawk\AuthClient\Util\LocalCacheFlusher;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

abstract class KeycloakQueryTestCase extends TestCase
{
    use PartialMockWithConstructorArgsTrait;

    /**
     * @var ConnectionConfig|(ConnectionConfig&object&\PHPUnit\Framework\MockObject\Stub)|(ConnectionConfig&\PHPUnit\Framework\MockObject\Stub)|(object&\PHPUnit\Framework\MockObject\Stub)|\PHPUnit\Framework\MockObject\Stub
     */
    protected ConnectionConfig $config;
    /**
     * @var ClientInterface|(ClientInterface&object&\PHPUnit\Framework\MockObject\MockObject)|(ClientInterface&\PHPUnit\Framework\MockObject\MockObject)|(object&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    protected ClientInterface $client;
    /**
     * @var LocalCacheFlusher|(LocalCacheFlusher&object&\PHPUnit\Framework\MockObject\MockObject)|(LocalCacheFlusher&\PHPUnit\Framework\MockObject\MockObject)|(object&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    protected LocalCacheFlusher $cacheFlusher;
    /**
     * @var UserFactory|(UserFactory&object&\PHPUnit\Framework\MockObject\MockObject)|(UserFactory&\PHPUnit\Framework\MockObject\MockObject)|(object&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    protected UserFactory $userFactory;
    /**
     * @var ResourceFactory|(ResourceFactory&object&\PHPUnit\Framework\MockObject\MockObject)|(ResourceFactory&\PHPUnit\Framework\MockObject\MockObject)|(object&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    protected ResourceFactory $resourceFactory;
    /**
     * @var ClockInterface|(object&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject|(ClockInterface&object&\PHPUnit\Framework\MockObject\MockObject)|(ClockInterface&\PHPUnit\Framework\MockObject\MockObject)
     */
    protected ClockInterface $clock;

    protected KeycloakApiClient $api;

    protected function setUp(): void
    {
        $this->client = $this->createMock(ClientInterface::class);
        $this->config = $this->createStub(ConnectionConfig::class);
        $this->config->method('getRealm')->willReturn('REALM');
        $this->config->method('getClientId')->willReturn('CLIENT_ID');
        $this->config->method('getClientSecret')->willReturn('CLIENT_SECRET');
        $this->config->method('getInternalKeycloakUrl')->willReturn('http://example.com');
        $clientUUid = new ClientUuid('f47ac10b-58cc-4372-a567-0e02b2c3d001');
        $connectionInfo = $this->createStub(ConnectionInfo::class);
        $connectionInfo->method('getClientUuid')->willReturn($clientUUid);
        $connectionInfoStorage = $this->createStub(ConnectionInfoStorage::class);
        $connectionInfoStorage->method('getConnectionInfo')->willReturn($connectionInfo);
        $token = new ApiToken('my-token', new \DateTimeImmutable());
        $tokenStorage = $this->createStub(ApiTokenStorage::class);
        $tokenStorage->method('getToken')->willReturn($token);

        $this->cacheFlusher = $this->createMock(LocalCacheFlusher::class);
        $this->userFactory = $this->createMock(UserFactory::class);
        $this->resourceFactory = $this->createMock(ResourceFactory::class);
        $this->clock = $this->createMock(ClockInterface::class);

        $this->api = $this->createPartialMockWithConstructorArgs(
            KeycloakApiClient::class,
            ['getConfiguredClient'],
            [
                $this->config,
                $this->client,
                $this->clock,
                $this->userFactory,
                $this->resourceFactory,
                $this->cacheFlusher
            ]
        );
        $this->api->method('getConfiguredClient')->willReturn($this->client);
        $this->api->setTokenStorage($tokenStorage);
        $this->api->setConnectionInfoStorage($connectionInfoStorage);
    }

    /**
     * @return ResponseInterface&\PHPUnit\Framework\MockObject\Stub
     */
    protected function createStreamResponse(string $content, int|null $statusCode = null): ResponseInterface
    {
        $response = $this->createStub(ResponseInterface::class);
        $stream = $this->createStub(StreamInterface::class);
        $stream->method('getContents')->willReturn($content);
        $response->method('getBody')->willReturn($stream);
        $response->method('getStatusCode')->willReturn($statusCode ?? 200);
        return $response;
    }

    protected function createClientNotAuthorizedException(string|null $content = null): ClientException
    {
        if ($content === null) {
            $content = json_encode(['error' => 'not_authorized']);
        }
        return $this->createClientException($content, 401);
    }

    protected function createClientException(string|null $responseContent = null, int|null $statusCode = null): ClientException
    {
        return new ClientException(
            'client error',
            $this->createStub(RequestInterface::class),
            $this->createStreamResponse($responseContent ?? '', $statusCode ?? 400)
        );
    }
}
