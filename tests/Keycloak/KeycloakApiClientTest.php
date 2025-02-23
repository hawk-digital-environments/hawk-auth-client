<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Keycloak;


use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7\Request;
use Hawk\AuthClient\Keycloak\ApiTokenStorage;
use Hawk\AuthClient\Keycloak\ConnectionInfoStorage;
use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Keycloak\Value\ApiToken;
use Hawk\AuthClient\Util\Uuid;
use Hawk\AuthClient\Keycloak\Value\ConnectionConfig;
use Hawk\AuthClient\Keycloak\Value\ConnectionInfo;
use Hawk\AuthClient\Resources\ResourceFactory;
use Hawk\AuthClient\Users\UserFactory;
use Hawk\AuthClient\Util\LocalCacheFlusher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Psr\Http\Message\ResponseInterface;

#[CoversClass(KeycloakApiClient::class)]
class KeycloakApiClientTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new KeycloakApiClient(
            $this->createStub(ConnectionConfig::class),
            $this->createStub(ClientInterface::class),
            $this->createStub(ClockInterface::class),
            $this->createStub(UserFactory::class),
            $this->createStub(ResourceFactory::class),
            $this->createStub(LocalCacheFlusher::class)
        );
        $this->assertInstanceOf(KeycloakApiClient::class, $sut);
    }

    public function testItCanSetTokenAndConnectionInfoStorage(): void
    {
        $tokenStorage = $this->createStub(ApiTokenStorage::class);
        $connectionInfoStorage = $this->createStub(ConnectionInfoStorage::class);
        $sut = new class extends KeycloakApiClient {
            /** @noinspection MagicMethodsValidityInspection */
            public function __construct()
            {
            }

            public function getStroage()
            {
                return [$this->tokenStorage, $this->connectionInfoStorage];
            }
        };

        $sut->setTokenStorage($tokenStorage);
        $sut->setConnectionInfoStorage($connectionInfoStorage);

        $this->assertSame([$tokenStorage, $connectionInfoStorage], $sut->getStroage());
    }

    public function testItCanCreateAConfiguredClient(): void
    {
        $config = $this->createStub(ConnectionConfig::class);
        $config->method('getRealm')->willReturn('REALM');
        $config->method('getClientId')->willReturn('CLIENT_ID');
        $config->method('getInternalKeycloakUrl')->willReturn('http://example.com');
        $clientUUid = new Uuid('f47ac10b-58cc-4372-a567-0e02b2c3d001');
        $connectionInfo = $this->createStub(ConnectionInfo::class);
        $connectionInfo->method('getClientUuid')->willReturn($clientUUid);
        $connectionInfoStorage = $this->createStub(ConnectionInfoStorage::class);
        $connectionInfoStorage->method('getConnectionInfo')->willReturn($connectionInfo);
        $token = new ApiToken('my-token', new \DateTimeImmutable());
        $tokenStorage = $this->createStub(ApiTokenStorage::class);
        $tokenStorage->method('getToken')->willReturn($token);

        $innerClient = $this->createMock(ClientInterface::class);
        $innerClient->expects($this->once())
            ->method('sendAsync')
            ->willReturnCallback(function (
                Request $request
            ) {
                $this->assertEquals('POST', $request->getMethod());
                $this->assertEquals('http://example.com/REALM/foo/CLIENT_ID/f47ac10b-58cc-4372-a567-0e02b2c3d001?foo=bar', (string)$request->getUri());
                $this->assertEquals('param1=value1', $request->getBody()->getContents());
                $this->assertEquals('Bearer my-token', $request->getHeaderLine('Authorization'));
                return new FulfilledPromise($this->createStub(ResponseInterface::class));
            });

        $sut = new class(
            $config,
            $innerClient,
            $this->createStub(ClockInterface::class),
            $this->createStub(UserFactory::class),
            $this->createStub(ResourceFactory::class),
            $this->createStub(LocalCacheFlusher::class)
        ) extends KeycloakApiClient {
            public function getClient(): ClientInterface
            {
                return $this->getConfiguredClient();
            }
        };

        $sut->setTokenStorage($tokenStorage);
        $sut->setConnectionInfoStorage($connectionInfoStorage);

        $client = $sut->getClient();

        $client->request('POST', '{realm}/foo/{clientId}/{clientUuid}', [
            'query' => [
                'foo' => 'bar'
            ],
            'form_params' => [
                'param1' => 'value1'
            ]
        ]);
    }

    public function testWhileRequestingTokenDoNotAddTheAuthorizationHeaderToConfiguredClient(): void
    {
        $config = $this->createStub(ConnectionConfig::class);
        $config->method('getRealm')->willReturn('REALM');
        $config->method('getClientId')->willReturn('CLIENT_ID');
        $config->method('getInternalKeycloakUrl')->willReturn('http://example.com');
        $clientUUid = new Uuid('f47ac10b-58cc-4372-a567-0e02b2c3d001');
        $connectionInfo = $this->createStub(ConnectionInfo::class);
        $connectionInfo->method('getClientUuid')->willReturn($clientUUid);
        $connectionInfoStorage = $this->createStub(ConnectionInfoStorage::class);
        $connectionInfoStorage->method('getConnectionInfo')->willReturn($connectionInfo);
        $tokenStorage = $this->createMock(ApiTokenStorage::class);
        $tokenStorage->expects($this->never())->method('getToken');

        $innerClient = $this->createMock(ClientInterface::class);
        $innerClient->expects($this->once())
            ->method('sendAsync')
            ->willReturnCallback(function (
                $request
            ) {
                $this->assertEquals('', $request->getHeaderLine('Authorization'));
                return new FulfilledPromise($this->createStub(ResponseInterface::class));
            });

        $sut = new class(
            $config,
            $innerClient,
            $this->createStub(ClockInterface::class),
            $this->createStub(UserFactory::class),
            $this->createStub(ResourceFactory::class),
            $this->createStub(LocalCacheFlusher::class)
        ) extends KeycloakApiClient {
            public function getClient(): ClientInterface
            {
                $this->isRequestingToken = true;
                return $this->getConfiguredClient();
            }
        };

        $sut->setTokenStorage($tokenStorage);
        $sut->setConnectionInfoStorage($connectionInfoStorage);

        $client = $sut->getClient();

        $client->request('GET', '{realm}/foo/{clientId}/{clientUuid}');
    }

    public function testDoNotSetAuthorizationHeaderIfItIsAlreadySet(): void
    {
        $config = $this->createStub(ConnectionConfig::class);
        $config->method('getRealm')->willReturn('REALM');
        $config->method('getClientId')->willReturn('CLIENT_ID');
        $config->method('getInternalKeycloakUrl')->willReturn('http://example.com');
        $clientUUid = new Uuid('f47ac10b-58cc-4372-a567-0e02b2c3d001');
        $connectionInfo = $this->createStub(ConnectionInfo::class);
        $connectionInfo->method('getClientUuid')->willReturn($clientUUid);
        $connectionInfoStorage = $this->createStub(ConnectionInfoStorage::class);
        $connectionInfoStorage->method('getConnectionInfo')->willReturn($connectionInfo);
        $tokenStorage = $this->createMock(ApiTokenStorage::class);
        $tokenStorage->expects($this->never())->method('getToken');

        $innerClient = $this->createMock(ClientInterface::class);
        $innerClient->expects($this->once())
            ->method('sendAsync')
            ->willReturnCallback(function (
                $request
            ) {
                $this->assertEquals('Bearer my-token', $request->getHeaderLine('Authorization'));
                return new FulfilledPromise($this->createStub(ResponseInterface::class));
            });

        $sut = new class(
            $config,
            $innerClient,
            $this->createStub(ClockInterface::class),
            $this->createStub(UserFactory::class),
            $this->createStub(ResourceFactory::class),
            $this->createStub(LocalCacheFlusher::class)
        ) extends KeycloakApiClient {
            public function getClient(): ClientInterface
            {
                return $this->getConfiguredClient();
            }
        };

        $sut->setTokenStorage($tokenStorage);
        $sut->setConnectionInfoStorage($connectionInfoStorage);

        $client = $sut->getClient();

        $client->request('GET', '{realm}/foo/{clientId}/{clientUuid}', [
            'headers' => [
                'Authorization' => 'Bearer my-token'
            ]
        ]);

    }
}
