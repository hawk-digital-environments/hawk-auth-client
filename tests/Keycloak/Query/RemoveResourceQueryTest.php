<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Keycloak\Query;

use Hawk\AuthClient\Exception\FailedToRemoveResourceException;
use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Keycloak\Query\RemoveResourceQuery;
use Hawk\AuthClient\Resources\Value\Resource;
use Hawk\AuthClient\Tests\TestUtils\DummyUuid;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(RemoveResourceQuery::class)]
#[CoversClass(KeycloakApiClient::class)]
#[CoversClass(FailedToRemoveResourceException::class)]
class RemoveResourceQueryTest extends KeycloakQueryTestCase
{
    public function testItCanDeleteAResource(): void
    {
        $resourceId = new DummyUuid();
        $resource = $this->createStub(Resource::class);
        $resource->method('getId')->willReturn($resourceId);

        $this->cacheFlusher->expects($this->once())->method('flushCache');

        $this->client->expects($this->once())
            ->method('request')
            ->with(
                'DELETE',
                'realms/{realm}/authz/protection/resource_set/' . $resourceId
            );

        $this->api->removeResource($resource);
    }

    public function testItFailsWithSpeakingExceptionIfResourceCouldNotBeDeleted(): void
    {
        $this->expectException(FailedToRemoveResourceException::class);

        $resourceId = new DummyUuid();
        $resource = $this->createStub(Resource::class);
        $resource->method('getId')->willReturn($resourceId);

        $this->cacheFlusher->expects($this->never())->method('flushCache');

        $this->client->expects($this->once())
            ->method('request')
            ->with(
                'DELETE',
                'realms/{realm}/authz/protection/resource_set/' . $resourceId
            )
            ->willThrowException($this->createClientNotAuthorizedException());

        $this->api->removeResource($resource);
    }
}
