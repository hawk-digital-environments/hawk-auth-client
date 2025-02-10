<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Auth;


use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Stream;
use Hawk\AuthClient\Auth\KeycloakProvider;
use Hawk\AuthClient\Exception\MissingUserFactoryCollaboratorException;
use Hawk\AuthClient\Users\UserFactory;
use Hawk\AuthClient\Users\Value\User;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

#[CoversClass(KeycloakProvider::class)]
#[CoversClass(MissingUserFactoryCollaboratorException::class)]
class KeycloakProviderTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new KeycloakProvider(
            [],
            [
                'userFactory' => $this->createStub(UserFactory::class),
            ]
        );
        $this->assertInstanceOf(KeycloakProvider::class, $sut);
    }

    public function testItFailsToConstructWithMissingUserFactory(): void
    {
        $this->expectException(MissingUserFactoryCollaboratorException::class);
        new KeycloakProvider();
    }

    public function testItCanGetTheBaseAuthorizationUrl(): void
    {
        // Invalid configuration, but it should not throw an exception
        $sut = new KeycloakProvider(collaborators: ['userFactory' => $this->createStub(UserFactory::class),]);
        $this->assertEquals('/realms//protocol/openid-connect/auth', $sut->getBaseAuthorizationUrl());

        // Valid configuration
        $sut = new KeycloakProvider(
            options: [
                'publicKeycloakUrl' => 'http://keycloak.example.com',
                'realm' => 'realm',
            ],
            collaborators: ['userFactory' => $this->createStub(UserFactory::class),]);
        $this->assertEquals('http://keycloak.example.com/realms/realm/protocol/openid-connect/auth', $sut->getBaseAuthorizationUrl());
    }

    public function testItCanGetTheBaseAccessTokenUrl(): void
    {
        // Invalid configuration, but it should not throw an exception
        $sut = new KeycloakProvider(collaborators: ['userFactory' => $this->createStub(UserFactory::class),]);
        $this->assertEquals('/realms//protocol/openid-connect/token', $sut->getBaseAccessTokenUrl());

        // Valid configuration
        $sut = new KeycloakProvider(
            options: [
                'internalKeycloakUrl' => 'http://keycloak',
                'realm' => 'realm',
            ],
            collaborators: ['userFactory' => $this->createStub(UserFactory::class),]);
        $this->assertEquals('http://keycloak/realms/realm/protocol/openid-connect/token', $sut->getBaseAccessTokenUrl());
    }

    public function testItCanGetResourceOwnerDetailsUrl(): void
    {
        // Invalid configuration, but it should not throw an exception
        $sut = new KeycloakProvider(collaborators: ['userFactory' => $this->createStub(UserFactory::class),]);
        $this->assertEquals('/realms//protocol/openid-connect/userinfo', $sut->getResourceOwnerDetailsUrl());

        // Valid configuration
        $sut = new KeycloakProvider(
            options: [
                'internalKeycloakUrl' => 'http://keycloak',
                'realm' => 'realm',
            ],
            collaborators: ['userFactory' => $this->createStub(UserFactory::class),]);
        $this->assertEquals('http://keycloak/realms/realm/protocol/openid-connect/userinfo', $sut->getResourceOwnerDetailsUrl());
    }

    public function testItCanGetLogoutUrlWithDefaultRedirectUrl(): void
    {
        $token = $this->createStub(AccessToken::class);
        $token->method('getValues')->willReturn(['id_token' => 'id_token']);
        $sut = new KeycloakProvider(
            options: [
                'publicKeycloakUrl' => 'http://keycloak',
                'redirectUriAfterLogout' => 'http://example.com/logout',
                'realm' => 'realm',
                'state' => 'STATE',
                'clientId' => 'CLIENT',
            ],
            collaborators: ['userFactory' => $this->createStub(UserFactory::class),]);
        $this->assertEquals(
            'http://keycloak/realms/realm/protocol/openid-connect/logout?id_token_hint=id_token&post_logout_redirect_uri=http%3A%2F%2Fexample.com%2Flogout&client_id=CLIENT&state=STATE',
            $sut->getLogoutUrl($token)
        );
    }

    public function testItCanGetLogoutUrlWithGivenRedirectUrl(): void
    {
        $token = $this->createStub(AccessToken::class);
        $token->method('getValues')->willReturn(['id_token' => 'id_token']);
        $sut = new KeycloakProvider(
            options: [
                'publicKeycloakUrl' => 'http://keycloak',
                'redirectUriAfterLogout' => 'http://example.com/logout',
                'realm' => 'realm',
                'state' => 'STATE',
                'clientId' => 'CLIENT',
            ],
            collaborators: ['userFactory' => $this->createStub(UserFactory::class)]);
        $this->assertEquals(
            'http://keycloak/realms/realm/protocol/openid-connect/logout?id_token_hint=id_token&post_logout_redirect_uri=http%3A%2F%2Fexample.com%2Fredirect&client_id=CLIENT&state=STATE',
            $sut->getLogoutUrl($token, 'http://example.com/redirect')
        );
    }

    public function testItCanCheckAResponse(): void
    {
        $sut = new class(collaborators: ['userFactory' => $this->createStub(UserFactory::class)]) extends KeycloakProvider {
            public function checkResponse(ResponseInterface $response, $data): void
            {
                parent::checkResponse($response, $data);
            }
        };

        $response = $this->createStub(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(400);
        $data = [];

        // Nothing should happen
        $sut->checkResponse($response, $data);

        try {
            $data['error'] = 'error';
            $data['error_description'] = 'description';
            $sut->checkResponse($response, $data);
            $this->fail('Expected exception');
        } catch (\Exception $e) {
            $this->assertInstanceOf(IdentityProviderException::class, $e);
            $this->assertEquals('error: description', $e->getMessage());
            $this->assertEquals(400, $e->getCode());
        }
    }

    public function testItCanBuildTheRequiredAuthorizationUrl(): void
    {
        $sut = new KeycloakProvider(
            options: [
                'publicKeycloakUrl' => 'http://keycloak.example.com',
                'internalKeycloakUrl' => 'http://keycloak',
                'realm' => 'realm',
            ],
            collaborators: ['userFactory' => $this->createStub(UserFactory::class)]);

        $this->assertEquals(
            'http://keycloak.example.com/realms/realm/protocol/openid-connect/auth?option=value&state=STATE&scope=profile%20email%20openid&response_type=code&approval_prompt=auto',
            $sut->getAuthorizationUrl(['option' => 'value', 'state' => 'STATE'])
        );
    }

    public function testItCanCreateTheResourceOwner(): void
    {
        $response = ['response' => 'data'];
        $user = $this->createStub(User::class);
        $userFactory = $this->createMock(UserFactory::class);
        $userFactory->expects($this->once())->method('makeUserFromKeycloakData')->with($response)->willReturn($user);

        $client = $this->createStub(Client::class);
        $body = $this->createStub(Stream::class);
        $body->method('getContents')->willReturn(json_encode($response));
        $body->method('__toString')->willReturn(json_encode($response));
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getBody')->willReturn($body);
        $client->method('send')->willReturn($response);

        $sut = new KeycloakProvider(
            options: [
                'publicKeycloakUrl' => 'http://keycloak.example.com',
                'internalKeycloakUrl' => 'http://keycloak',
                'realm' => 'realm',
            ],
            collaborators: ['userFactory' => $userFactory, 'httpClient' => $client]);

        $this->assertSame($user, $sut->getResourceOwner($this->createStub(AccessToken::class)));
    }

}
