<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Keycloak\Query;

use Hawk\AuthClient\Exception\FailedRequestDueToMissingPermissionsException;
use Hawk\AuthClient\Exception\FailedToWriteResourceException;
use Hawk\AuthClient\Exception\ResourceAlreadyExistsException;
use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Keycloak\Query\UpsertResourceQuery;
use Hawk\AuthClient\Resources\Value\ResourceBuilder;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UpsertResourceQuery::class)]
#[CoversClass(KeycloakApiClient::class)]
#[CoversClass(FailedRequestDueToMissingPermissionsException::class)]
#[CoversClass(ResourceAlreadyExistsException::class)]
#[CoversClass(FailedToWriteResourceException::class)]
class UpsertResourceQueryTest extends KeycloakQueryTestCase
{
    public function testItCanCreateANewResource(): void
    {
        $builder = $this->createStub(ResourceBuilder::class);
        $builder->method('doesUpdateExistingResource')->willReturn(false);
        $builder->method('jsonSerialize')->willReturn(['name' => 'test']);

        $this->client->method('request')
            ->with(
                'POST',
                'realms/{realm}/authz/protection/resource_set',
                ['json' => ['name' => 'test']]
            );

        $this->cacheFlusher->expects($this->once())->method('flushCache');

        $this->api->upsertResource($builder);
    }

    public function testItFailsToCreateANewResourceDueToMissingPermissions(): void
    {
        $this->expectException(FailedRequestDueToMissingPermissionsException::class);
        $this->expectExceptionMessage('hawk-manage-resource-permissions');
        $builder = $this->createStub(ResourceBuilder::class);
        $builder->method('doesUpdateExistingResource')->willReturn(false);
        $this->client->method('request')->willThrowException($this->createClientNotAuthorizedException());
        $this->api->upsertResource($builder);
    }

    public function testItFailsToCreateANewResourceBecauseItAlreadyExists(): void
    {
        $this->expectException(ResourceAlreadyExistsException::class);
        $builder = $this->createStub(ResourceBuilder::class);
        $builder->method('doesUpdateExistingResource')->willReturn(false);
        $this->client->method('request')->willThrowException($this->createClientException(statusCode: 409));
        $this->api->upsertResource($builder);
    }

    public function testItFailsToCreateANewResourceDueToGenericError(): void
    {
        $this->expectException(FailedToWriteResourceException::class);
        $builder = $this->createStub(ResourceBuilder::class);
        $builder->method('doesUpdateExistingResource')->willReturn(false);
        $this->client->method('request')->willThrowException($this->createClientException());
        $this->api->upsertResource($builder);
    }

    public function testItCanUpdateAnExistingResource(): void
    {
        $builder = $this->createStub(ResourceBuilder::class);
        $builder->method('doesUpdateExistingResource')->willReturn(true);
        $builder->method('getId')->willReturn('resource-id');
        $builder->method('jsonSerialize')->willReturn(['name' => 'test']);

        $this->client->method('request')
            ->with(
                'PUT',
                'realms/{realm}/authz/protection/resource_set/' . $builder->getId(),
                ['json' => ['name' => 'test']]
            );

        $this->cacheFlusher->expects($this->once())->method('flushCache');

        $this->api->upsertResource($builder);
    }

    public function testItFailsToUpdateAnExistingResourceDueToMissingPermissions(): void
    {
        $this->expectException(FailedRequestDueToMissingPermissionsException::class);
        $this->expectExceptionMessage('hawk-manage-resource-permissions');
        $builder = $this->createStub(ResourceBuilder::class);
        $builder->method('doesUpdateExistingResource')->willReturn(true);
        $this->client->method('request')->willThrowException($this->createClientNotAuthorizedException());
        $this->api->upsertResource($builder);
    }

    public function testItFailsToUpdateAnExistingResourceDueToGenericError(): void
    {
        $this->expectException(FailedToWriteResourceException::class);
        $builder = $this->createStub(ResourceBuilder::class);
        $builder->method('doesUpdateExistingResource')->willReturn(true);
        $this->client->method('request')->willThrowException($this->createClientException());
        $this->api->upsertResource($builder);
    }
}
