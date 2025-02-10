<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Keycloak\Query;

use GuzzleHttp\Exception\RequestException;
use Hawk\AuthClient\Exception\FailedRequestDueToMissingPermissionsException;
use Hawk\AuthClient\Exception\ProfileDefinitionFailedException;
use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Keycloak\Query\UpdateProfileStructureQuery;
use Hawk\AuthClient\Profiles\Structure\Util\ProfileStructureData;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UpdateProfileStructureQuery::class)]
#[CoversClass(KeycloakApiClient::class)]
#[CoversClass(FailedRequestDueToMissingPermissionsException::class)]
#[CoversClass(ProfileDefinitionFailedException::class)]
class UpdateProfileStructureQueryTest extends KeycloakQueryTestCase
{
    public function testItUpdatesTheProfileStructureDataCorrectly(): void
    {
        $payload = ['foo' => 'bar'];
        $data = $this->createStub(ProfileStructureData::class);
        $data->method('jsonSerialize')->willReturn($payload);

        $this->client->expects($this->once())
            ->method('request')
            ->with(
                'PUT',
                'realms/{realm}/hawk/profile/structure',
                ['json' => $payload]
            );

        $this->api->updateProfileStructure($data);
    }

    public function testItFailsToUpdateProfileStructureDataDueToMissingPermissions(): void
    {
        $this->expectException(FailedRequestDueToMissingPermissionsException::class);
        $this->expectExceptionMessage('hawk-manage-profile-structure');

        $data = $this->createStub(ProfileStructureData::class);
        $this->client->expects($this->once())
            ->method('request')
            ->willThrowException($this->createClientNotAuthorizedException());

        $this->api->updateProfileStructure($data);
    }

    public function testItFailsToUpdateProfileStructureDataDueToGenericError(): void
    {
        $this->expectException(ProfileDefinitionFailedException::class);

        $exception = $this->createStub(RequestException::class);
        $exception->method('getResponse')->willReturn($this->createStreamResponse(''));

        $data = $this->createStub(ProfileStructureData::class);
        $this->client->expects($this->once())
            ->method('request')
            ->willThrowException($exception);

        $this->api->updateProfileStructure($data);
    }
}
