<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Keycloak\Query;


use GuzzleHttp\Exception\RequestException;
use Hawk\AuthClient\Exception\FailedRequestDueToMissingPermissionsException;
use Hawk\AuthClient\Exception\ProfileUpdateDataInvalidException;
use Hawk\AuthClient\Exception\ProfileUpdateFailedException;
use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Keycloak\Query\UpdateProfileDataQuery;
use Hawk\AuthClient\Keycloak\Value\ConnectionConfig;
use Hawk\AuthClient\Profiles\Value\ProfileFieldValidationError;
use Hawk\AuthClient\Profiles\Value\UserProfile;
use Hawk\AuthClient\Tests\TestUtils\DummyUuid;
use Hawk\AuthClient\Users\Value\User;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UpdateProfileDataQuery::class)]
#[CoversClass(KeycloakApiClient::class)]
#[CoversClass(FailedRequestDueToMissingPermissionsException::class)]
#[CoversClass(ProfileUpdateDataInvalidException::class)]
#[CoversClass(ProfileUpdateFailedException::class)]
#[CoversClass(ProfileFieldValidationError::class)]
class UpdateProfileDataQueryTest extends KeycloakQueryTestCase
{
    public function testItCanUpdateTheUserProfileData(): void
    {
        $config = $this->createStub(ConnectionConfig::class);
        $userId = new DummyUuid();
        $user = $this->createStub(User::class);
        $user->method('getId')->willReturn($userId);

        $profile = new UserProfile(
            $config,
            'username',
            'firstName',
            'lastName',
            'email',
            [
                'key' => 'value'
            ],
            [
                'structure' => 'data'
            ],
            [
                'emailVerified' => true
            ]
        );

        $changeSet = [
            UserProfile::ATTRIBUTE_USERNAME => 'new-username',
            UserProfile::ATTRIBUTE_FIRST_NAME => 'new-first-name',
            UserProfile::ATTRIBUTE_LAST_NAME => 'new-last-name',
            UserProfile::ATTRIBUTE_EMAIL => 'new-email',
            'key' => 'new-value',
        ];

        $expectedPayload = [
            'username' => 'new-username',
            'firstName' => 'new-first-name',
            'lastName' => 'new-last-name',
            'email' => 'new-email',
            'attributes' => [
                'key' => 'new-value'
            ]
        ];

        $this->client->expects($this->once())
            ->method('request')
            ->with(
                'PATCH',
                'realms/{realm}/hawk/profile/' . $userId,
                [
                    'query' => [
                        'mode' => 'user'
                    ],
                    'json' => $expectedPayload
                ]
            );

        $this->api = $this->createClientWithDummyProfile($user, $profile);

        $this->api->updateUserProfile($user, $changeSet, false);
    }

    public function testItCanUpdateUserProfileDataAsAdmin(): void
    {
        $userId = new DummyUuid();
        $user = $this->createStub(User::class);
        $user->method('getId')->willReturn($userId);
        $this->client->expects($this->once())
            ->method('request')
            ->with(
                'PATCH',
                'realms/{realm}/hawk/profile/' . $userId,
                [
                    'query' => [
                        'mode' => 'user'
                    ],
                    'json' => [
                        'attributes' => [
                            'attribute' => ['value']
                        ]
                    ]
                ]
            );

        $changeSet = [
            'attribute' => ['value']
        ];

        $profile = $this->createStub(UserProfile::class);

        $this->api = $this->createClientWithDummyProfile($user, $profile);

        $this->api->updateUserProfile($user, $changeSet, false);
    }

    public function testItFailsToUpdateTheUserProfileDueToMissingPermissions(): void
    {
        $this->expectException(FailedRequestDueToMissingPermissionsException::class);
        $this->expectExceptionMessage('hawk-manage-profile-data');

        $this->client->expects($this->once())
            ->method('request')
            ->willThrowException($this->createClientNotAuthorizedException());

        $this->api = $this->createClientWithDummyProfile($this->createStub(User::class), $this->createStub(UserProfile::class));

        $this->api->updateUserProfile($this->createStub(User::class), [], false);
    }

    public function testItFailsToUpdateTheUserProfileDueToValidationError(): void
    {
        $errorResponse = <<<JSON
{
  "error": "Invalid request",
  "field": "username",
  "errors": [
    {
    "field": "username",
    "errorMessage": "Username must be at least 3 characters long",
    "params": []
    }
  ]
}
JSON;

        $response = $this->createStreamResponse($errorResponse, 400);
        $exception = $this->createStub(RequestException::class);
        $exception->method('getResponse')->willReturn($response);

        $this->client->expects($this->once())
            ->method('request')
            ->willThrowException($exception);

        try {
            $this->api = $this->createClientWithDummyProfile($this->createStub(User::class), $this->createStub(UserProfile::class));
            $this->api->updateUserProfile($this->createStub(User::class), [], false);
            $this->fail('Exception not thrown');
        } catch (\Throwable $e) {
            $this->assertInstanceOf(ProfileUpdateDataInvalidException::class, $e);
            $this->assertEquals('Error while updating field username', $e->getMessage());
            $errors = $e->getErrors();
            $this->assertCount(1, $errors);
            $this->assertInstanceOf(ProfileFieldValidationError::class, $errors[0]);
            $this->assertEquals('username', $errors[0]->getField());
            $this->assertEquals('Username must be at least 3 characters long', $errors[0]->getMessage());
            $this->assertEquals([], $errors[0]->getParams());
        }
    }

    public function testItFailsToUpdateTheUserProfileDueToGenericError(): void
    {
        $response = $this->createStreamResponse('broken!', 400);
        $exception = $this->createStub(RequestException::class);
        $exception->method('getResponse')->willReturn($response);

        $this->client->expects($this->once())
            ->method('request')
            ->willThrowException($exception);

        $this->expectException(ProfileUpdateFailedException::class);

        $this->api = $this->createClientWithDummyProfile($this->createStub(User::class), $this->createStub(UserProfile::class));
        $this->api->updateUserProfile($this->createStub(User::class), [], false);
    }

    protected function createClientWithDummyProfile(User $user, UserProfile $profile): KeycloakApiClient
    {
        $api = $this->createPartialMockWithConstructorArgs(
            KeycloakApiClient::class,
            ['getConfiguredClient', 'fetchUserProfile'],
            [
                $this->config,
                $this->client,
                $this->clock,
                $this->userFactory,
                $this->resourceFactory,
                $this->cacheFlusher
            ]
        );
        $api->method('getConfiguredClient')->willReturn($this->client);
        $api->method('fetchUserProfile')->with($user)->willReturn($profile);
        return $api;
    }

}
