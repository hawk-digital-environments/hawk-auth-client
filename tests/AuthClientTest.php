<?php
/** @noinspection PhpParamsInspection */
declare(strict_types=1);

namespace Hawk\AuthClient\Tests;

use GuzzleHttp\ClientInterface;
use Hawk\AuthClient\Auth\KeycloakProvider;
use Hawk\AuthClient\Auth\StatefulAuth;
use Hawk\AuthClient\Auth\StatelessAuth;
use Hawk\AuthClient\AuthClient;
use Hawk\AuthClient\Cache\CacheAdapterInterface;
use Hawk\AuthClient\Container;
use Hawk\AuthClient\Groups\GroupStorage;
use Hawk\AuthClient\Keycloak\Value\ConnectionConfig;
use Hawk\AuthClient\Permissions\GuardFactory;
use Hawk\AuthClient\Profiles\ProfileLayer;
use Hawk\AuthClient\Request\RequestAdapterInterface;
use Hawk\AuthClient\Resources\ResourceStorage;
use Hawk\AuthClient\Roles\RoleStorage;
use Hawk\AuthClient\Session\SessionAdapterInterface;
use Hawk\AuthClient\Users\UserStorage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;

#[CoversClass(AuthClient::class)]
class AuthClientTest extends TestCase
{
    protected const array DEFAULT_CONFIG = [
        'http://localhost/callback',
        'http://localhost:8088',
        'dev',
        'hawk',
        'clientsecret'
    ];

    public function testItConstructs(): void
    {
        $sut = new AuthClient(...self::DEFAULT_CONFIG);
        $this->assertInstanceOf(AuthClient::class, $sut);
    }

    public function testItExecutesContainerFactory(): void
    {
        $wasExecuted = false;
        $containerFactory = function () use (&$wasExecuted) {
            $wasExecuted = true;
            return $this->createStub(Container::class);
        };
        new AuthClient(...self::DEFAULT_CONFIG, containerFactory: $containerFactory);
        $this->assertTrue($wasExecuted, 'Container factory was not executed');
    }

    public function testItProvidesAllDataToTheContainer(): void
    {
        [$redirectUrl, $publicKeycloakUrl, $realm, $clientId, $clientSecret] = self::DEFAULT_CONFIG;
        $redirectUrlAfterLogout = 'http://localhost:8088/logout';
        $internalKeycloakUrl = 'http://localhost:9999';
        $providerOptions = ['option1' => 'value1'];
        $providerCollaborators = ['collaborator1' => 'value1'];

        $cache = $this->createStub(CacheAdapterInterface::class);
        $session = $this->createStub(SessionAdapterInterface::class);
        $request = $this->createStub(RequestAdapterInterface::class);
        $clock = $this->createStub(ClockInterface::class);
        $httpClient = $this->createStub(ClientInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $containerMock = $this->createMock(Container::class);
        $containerMock->expects($this->once())
            ->method('setCache')
            ->with($cache)
            ->willReturnSelf();
        $containerMock->expects($this->once())
            ->method('setSession')
            ->with($session)
            ->willReturnSelf();
        $containerMock->expects($this->once())
            ->method('setRequest')
            ->with($request)
            ->willReturnSelf();
        $containerMock->expects($this->once())
            ->method('setClock')
            ->with($clock)
            ->willReturnSelf();
        $containerMock->expects($this->once())
            ->method('setHttpClient')
            ->with($httpClient)
            ->willReturnSelf();
        $containerMock->expects($this->once())
            ->method('setLogger')
            ->with($logger)
            ->willReturnSelf();

        $containerFactory = function ($config, $_providerOptions, $_providerCollaborators) use (
            $redirectUrl,
            $publicKeycloakUrl,
            $realm,
            $clientId,
            $clientSecret,
            $redirectUrlAfterLogout,
            $internalKeycloakUrl,
            $providerOptions,
            $providerCollaborators,
            $containerMock
        ) {
            $this->assertInstanceOf(ConnectionConfig::class, $config);
            /** @var ConnectionConfig $config */
            $this->assertEquals($redirectUrl, $config->getRedirectUrl());
            $this->assertEquals($publicKeycloakUrl, $config->getPublicKeycloakUrl());
            $this->assertEquals($realm, $config->getRealm());
            $this->assertEquals($clientId, $config->getClientId());
            $this->assertEquals($clientSecret, $config->getClientSecret());
            $this->assertEquals($redirectUrlAfterLogout, $config->getRedirectUrlAfterLogout());
            $this->assertEquals($internalKeycloakUrl, $config->getInternalKeycloakUrl());
            $this->assertEquals($providerOptions, $_providerOptions);
            $this->assertEquals($providerCollaborators, $_providerCollaborators);

            return $containerMock;
        };

        $sut = new AuthClient(
            $redirectUrl,
            $publicKeycloakUrl,
            $realm,
            $clientId,
            $clientSecret,
            $redirectUrlAfterLogout,
            $internalKeycloakUrl,
            $providerOptions,
            $providerCollaborators,
            $cache,
            $session,
            $request,
            $clock,
            $httpClient,
            $logger,
            $containerFactory
        );
        $this->assertInstanceOf(AuthClient::class, $sut);
    }

    public function testAllLayersAreAccessible(): void
    {
        $oauthProvider = $this->createStub(KeycloakProvider::class);
        $userLayer = $this->createStub(UserStorage::class);
        $statefulAuth = $this->createStub(StatefulAuth::class);
        $statelessAuth = $this->createStub(StatelessAuth::class);
        $guardLayer = $this->createStub(GuardFactory::class);
        $groupLayer = $this->createStub(GroupStorage::class);
        $roleLayer = $this->createStub(RoleStorage::class);
        $profileLayer = $this->createStub(ProfileLayer::class);
        $resourceLayer = $this->createStub(ResourceStorage::class);

        $containerMock = $this->createMock(Container::class);
        $containerMock->expects($this->once())
            ->method('getKeycloakOauthProvider')
            ->willReturn($oauthProvider);
        $containerMock->expects($this->once())
            ->method('getUserStorage')
            ->willReturn($userLayer);
        $containerMock->expects($this->once())
            ->method('getStatefulAuth')
            ->willReturn($statefulAuth);
        $containerMock->expects($this->once())
            ->method('getStatelessAuth')
            ->willReturn($statelessAuth);
        $containerMock->expects($this->once())
            ->method('getGuardFactory')
            ->willReturn($guardLayer);
        $containerMock->expects($this->once())
            ->method('getGroupStorage')
            ->willReturn($groupLayer);
        $containerMock->expects($this->once())
            ->method('getRoleStorage')
            ->willReturn($roleLayer);
        $containerMock->expects($this->once())
            ->method('getProfileLayer')
            ->willReturn($profileLayer);
        $containerMock->expects($this->once())
            ->method('getResourceStorage')
            ->willReturn($resourceLayer);

        $sut = new AuthClient(...self::DEFAULT_CONFIG, containerFactory: fn() => $containerMock);

        $this->assertSame($oauthProvider, $sut->oauthProvider());
        $this->assertSame($userLayer, $sut->users());
        $this->assertSame($statefulAuth, $sut->statefulAuth());
        $this->assertSame($statelessAuth, $sut->statelessAuth());
        $this->assertSame($guardLayer, $sut->guard());
        $this->assertSame($groupLayer, $sut->groups());
        $this->assertSame($roleLayer, $sut->roles());
        $this->assertSame($profileLayer, $sut->profile());
        $this->assertSame($resourceLayer, $sut->resources());
    }
}
