<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Keycloak\Query;

use Hawk\AuthClient\Exception\FailedToRemoveResourceException;
use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Keycloak\Query\RemoveResourceQuery;
use Hawk\AuthClient\Resources\Value\Resource;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(RemoveResourceQuery::class)]
#[CoversClass(KeycloakApiClient::class)]
#[CoversClass(FailedToRemoveResourceException::class)]
class RemoveResourceQueryTest extends KeycloakQueryTestCase
{
    public function testItCanDeleteAResource(): void
    {
        $resource = $this->createStub(Resource::class);
        $resource->method('getId')->willReturn('resource-id');

        $this->cacheFlusher->expects($this->once())->method('flushCache');

        $this->client->expects($this->once())
            ->method('request')
            ->with(
                'DELETE',
                'realms/{realm}/authz/protection/resource_set/' . $resource->getId()
            );

        $this->api->removeResource($resource);
    }

    public function testItFailsWithSpeakingExceptionIfResourceCouldNotBeDeleted(): void
    {
        $this->expectException(FailedToRemoveResourceException::class);
        
        $resource = $this->createStub(Resource::class);
        $resource->method('getId')->willReturn('resource-id');

        $this->cacheFlusher->expects($this->never())->method('flushCache');

        $this->client->expects($this->once())
            ->method('request')
            ->with(
                'DELETE',
                'realms/{realm}/authz/protection/resource_set/' . $resource->getId()
            )
            ->willThrowException($this->createClientNotAuthorizedException());

        $this->api->removeResource($resource);
    }
}
