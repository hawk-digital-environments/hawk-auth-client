<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Keycloak\Query;


use Hawk\AuthClient\Exception\FailedRequestDueToMissingPermissionsException;
use Hawk\AuthClient\Exception\FailedToSetUserResourcePermissionException;
use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Keycloak\Query\SetResourceUserPermissionsQuery;
use Hawk\AuthClient\Resources\Value\Resource;
use Hawk\AuthClient\Tests\TestUtils\DummyUuid;
use Hawk\AuthClient\Users\Value\User;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(SetResourceUserPermissionsQuery::class)]
#[CoversClass(KeycloakApiClient::class)]
#[CoversClass(FailedRequestDueToMissingPermissionsException::class)]
#[CoversClass(FailedToSetUserResourcePermissionException::class)]
class SetResourceUserPermissionsQueryTest extends KeycloakQueryTestCase
{
    public function testItCanUpdateUserPermissions(): void
    {
        $resourceId = new DummyUuid(1);
        $resource = $this->createStub(Resource::class);
        $resource->method('getId')->willReturn($resourceId);
        $userId = new DummyUuid(2);
        $user = $this->createStub(User::class);
        $user->method('getId')->willReturn($userId);
        $scopes = ['read', 'write'];

        $this->cacheFlusher->expects($this->once())->method('flushCache');

        $this->client->expects($this->once())
            ->method('request')
            ->with(
                'PUT',
                'realms/{realm}/hawk/resources/' . $resourceId . '/users/' . $userId,
                ['json' => ['scopes' => $scopes]]
            );

        $this->api->setResourceUserPermissions($resource, $user, $scopes);
    }

    public function testItFailsToUpdateUserPermissionsIfUnauthorized(): void
    {
        $resourceId = new DummyUuid(1);
        $resource = $this->createStub(Resource::class);
        $resource->method('getId')->willReturn($resourceId);
        $userId = new DummyUuid(2);
        $user = $this->createStub(User::class);
        $user->method('getId')->willReturn($userId);
        $scopes = ['read', 'write'];

        $this->cacheFlusher->expects($this->never())->method('flushCache');

        $this->client->expects($this->once())
            ->method('request')
            ->with(
                'PUT',
                'realms/{realm}/hawk/resources/' . $resourceId . '/users/' . $userId,
                ['json' => ['scopes' => $scopes]]
            )
            ->willThrowException($this->createClientNotAuthorizedException());

        $this->expectException(FailedRequestDueToMissingPermissionsException::class);
        $this->expectExceptionMessage('hawk-manage-resource-permissions');

        $this->api->setResourceUserPermissions($resource, $user, $scopes);
    }

    public function testItFailsToUpdateUserPermissionsWithSpeakingException(): void
    {
        $resourceId = new DummyUuid(1);
        $resource = $this->createStub(Resource::class);
        $resource->method('getId')->willReturn($resourceId);
        $resource->method('getName')->willReturn('resource-name');
        $userId = new DummyUuid(2);
        $user = $this->createStub(User::class);
        $user->method('getId')->willReturn($userId);
        $user->method('getUsername')->willReturn('user-username');
        $scopes = ['read', 'write'];

        $this->cacheFlusher->expects($this->never())->method('flushCache');

        $this->client->expects($this->once())
            ->method('request')
            ->with(
                'PUT',
                'realms/{realm}/hawk/resources/' . $resourceId . '/users/' . $userId,
                ['json' => ['scopes' => $scopes]]
            )
            ->willThrowException($this->createClientException());

        $this->expectException(FailedToSetUserResourcePermissionException::class);

        $this->api->setResourceUserPermissions($resource, $user, $scopes);
    }
}
