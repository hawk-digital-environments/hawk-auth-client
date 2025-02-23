<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Users;


use Hawk\AuthClient\Exception\MissingHawkUserClaimException;
use Hawk\AuthClient\Exception\MissingUserIdClaimException;
use Hawk\AuthClient\Groups\Value\GroupReferenceList;
use Hawk\AuthClient\Keycloak\Value\ConnectionConfig;
use Hawk\AuthClient\Roles\Value\RoleReferenceList;
use Hawk\AuthClient\Users\UserFactory;
use Hawk\AuthClient\Users\Value\User;
use Hawk\AuthClient\Users\Value\UserClaims;
use Hawk\AuthClient\Users\Value\UserContext;
use Hawk\AuthClient\Util\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UserFactory::class)]
#[CoversClass(MissingUserIdClaimException::class)]
#[CoversClass(MissingHawkUserClaimException::class)]
class UserFactoryTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new UserFactory($this->createStub(ConnectionConfig::class), $this->createStub(UserContext::class));
        $this->assertInstanceOf(UserFactory::class, $sut);
    }

    public function testItCanCreateAUserFromKeycloakData(): void
    {
        $userData = <<<JSON
{
    "sub": "83335934-fc49-4c59-8199-de47c3d03ac5",
    "hawk": {
        "roles": {
            "realm": [
                "default-roles-users",
                "realm-role"
            ],
            "client": {
                "clientId": [
                    "uma_protection",
                    "client-role"
                ]
            }
        },
        "groups": [
            "offline_access",
            "custom-group"
        ]
    },
    "email_verified": false,
    "realm_access": {
        "roles": [
            "default-roles-users",
            "realm-role"
        ]
    },
    "preferred_username": "service-account-clientId"
}
JSON;

        $connectionConfig = $this->createStub(ConnectionConfig::class);
        $connectionConfig->method('getClientId')->willReturn('clientId');
        $userContext = $this->createStub(UserContext::class);

        $expectedUser = new User(
            new Uuid('83335934-fc49-4c59-8199-de47c3d03ac5'),
            'service-account-clientId',
            new UserClaims(['email_verified' => false]),
            RoleReferenceList::fromScalarList('realm-role', 'client-role'),
            GroupReferenceList::fromScalarList('custom-group'),
            $userContext
        );

        $user = (new UserFactory($connectionConfig, $userContext))
            ->makeUserFromKeycloakData(json_decode($userData, true));

        $this->assertEquals($expectedUser, $user);
    }

    public function testItFailsToCreateAUserFromKeycloakDataIfSidIsMissing(): void
    {
        $this->expectException(MissingUserIdClaimException::class);

        $userData = <<<JSON
{}
JSON;

        $connectionConfig = $this->createStub(ConnectionConfig::class);
        $connectionConfig->method('getClientId')->willReturn('clientId');
        $userContext = $this->createStub(UserContext::class);

        (new UserFactory($connectionConfig, $userContext))
            ->makeUserFromKeycloakData(json_decode($userData, true));
    }

    public function testItFailsToCreateAUserFromKeycloakDataIfHawkNodeIsMissing(): void
    {
        $this->expectException(MissingHawkUserClaimException::class);

        $userData = <<<JSON
{
    "sub": "83335934-fc49-4c59-8199-de47c3d03ac5",
    "email_verified": false,
    "realm_access": {
        "roles": [
            "default-roles-users",
            "realm-role"
        ]
    },
    "preferred_username": "service-account-clientId"
}
JSON;

        $connectionConfig = $this->createStub(ConnectionConfig::class);
        $connectionConfig->method('getClientId')->willReturn('clientId');
        $userContext = $this->createStub(UserContext::class);

        (new UserFactory($connectionConfig, $userContext))
            ->makeUserFromKeycloakData(json_decode($userData, true));
    }

    public function testItCanCreateAUserFromCacheData(): void
    {
        $userContext = $this->createStub(UserContext::class);
        $user1 = new User(
            new Uuid('f47ac10b-58cc-4372-a567-0e02b2c3d001'),
            'username',
            new UserClaims(['claim' => 'value']),
            RoleReferenceList::fromScalarList('role', 'otherRole'),
            GroupReferenceList::fromScalarList('group'),
            $userContext
        );

        $user2 = (new UserFactory($this->createStub(ConnectionConfig::class), $userContext))
            ->makeUserFromCacheData(json_decode(json_encode($user1), true));

        $this->assertEquals($user1, $user2);
    }
}
