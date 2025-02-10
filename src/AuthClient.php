<?php
declare(strict_types=1);

namespace Hawk\AuthClient;

use GuzzleHttp\ClientInterface;
use Hawk\AuthClient\Auth\KeycloakProvider;
use Hawk\AuthClient\Auth\StatefulAuth;
use Hawk\AuthClient\Auth\StatelessAuth;
use Hawk\AuthClient\Cache\CacheAdapterInterface;
use Hawk\AuthClient\Keycloak\Value\ConnectionConfig;
use Hawk\AuthClient\Layers\GroupLayerInterface;
use Hawk\AuthClient\Layers\GuardLayerInterface;
use Hawk\AuthClient\Layers\ProfileLayerInterface;
use Hawk\AuthClient\Layers\ResourceLayerInterface;
use Hawk\AuthClient\Layers\RoleLayerInterface;
use Hawk\AuthClient\Layers\UserLayerInterface;
use Hawk\AuthClient\Request\RequestAdapterInterface;
use Hawk\AuthClient\Session\SessionAdapterInterface;
use League\OAuth2\Client\Provider\AbstractProvider;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use SensitiveParameter;

class AuthClient
{
    private Container $container;

    /**
     * AuthClient constructor.
     *
     * @param string $redirectUrl The URL to redirect to, when the user got authenticated by Keycloak.
     *                            This url will receive the access token / authorization code.
     * @param string $publicKeycloakUrl The fully qualified, public URL of the Keycloak instance.
     *                                  This url is used to redirect the enduser, if you are running in a docker/kubernetes environment
     *                                  you might want to use the {@see internalKeycloakUrl} additionally to communicate with Keycloak directly.
     * @param string $realm The realm to use for authentication. You get this value from the Keycloak admin interface.
     * @param string $clientId The client id to use for authentication. You get this value from the Keycloak admin interface.
     *                         This is the readable name of the client, not the uuid!
     * @param string $clientSecret The client secret to use for authentication. You get this value from the Keycloak admin interface.
     *                             Currently only the "Client Id and Secret" authentication is supported.
     * @param string|null $redirectUrlAfterLogout Optional URL to redirect to after the user has logged out. If omitted, the user will be redirected to the login page.
     * @param string|null $internalKeycloakUrl Optional url, if you are running in a docker/kubernetes environment and want to communicate with Keycloak directly.
     * @param array|null $providerOptions Optional options to pass to the {@see AbstractProvider::__construct} of the League OAuth2 Client.
     * @param array|null $providerCollaborators Optional collaborators to pass to the {@see AbstractProvider::__construct} of the League OAuth2 Client.
     * @param CacheAdapterInterface|null $cache Optional cache adapter to use for caching user data. It is HIGHLY recommended to use a efficient cache adapter like redis or memcached here.
     * @param SessionAdapterInterface|null $session Optional adapter to read and write data into the user session. If omitted, the default PHP session is used.
     * @param RequestAdapterInterface|null $request Optional adapter to read data from the request. If omitted, the default PHP $_GET is used.
     * @param ClockInterface|null $clock Optional clock to use for time related operations. If omitted, the default system clock is used.
     * @param ClientInterface|null $httpClient Optional http client to use for http requests. If omitted, a not configured {@see ClientInterface} client is used.
     * @param LoggerInterface|null $logger Optional logger to use for logging. If omitted, no logging is done.
     * @param callable():Container|null $containerFactory For testing purposes only! Allows to inject a custom container factory.
     */
    public function __construct(
        string                       $redirectUrl,
        string                       $publicKeycloakUrl,
        string                       $realm,
        #[SensitiveParameter]
        string                       $clientId,
        #[SensitiveParameter]
        string                       $clientSecret,
        string|null                  $redirectUrlAfterLogout = null,
        string|null                  $internalKeycloakUrl = null,
        array|null                   $providerOptions = null,
        array|null                   $providerCollaborators = null,
        CacheAdapterInterface|null   $cache = null,
        SessionAdapterInterface|null $session = null,
        RequestAdapterInterface|null $request = null,
        ClockInterface|null          $clock = null,
        ClientInterface|null         $httpClient = null,
        LoggerInterface|null         $logger = null,
        callable|null                $containerFactory = null
    )
    {
        $config = new ConnectionConfig(
            $redirectUrl,
            $redirectUrlAfterLogout,
            $publicKeycloakUrl,
            $internalKeycloakUrl,
            $clientId,
            $clientSecret,
            $realm
        );

        $providerOptions ??= [];
        $providerCollaborators ??= [];

        if ($containerFactory === null) {
            $containerFactory = static fn($a, $b, $c) => new Container($a, $b, $c);
        }

        $this->container = ($containerFactory)($config, $providerOptions, $providerCollaborators);
        $this->container
            ->setCache($cache)
            ->setSession($session)
            ->setRequest($request)
            ->setClock($clock)
            ->setHttpClient($httpClient)
            ->setLogger($logger);
    }

    /**
     * Returns the configured {@link KeycloakProvider} instance of the League OAuth2 Client.
     * Normally you should not need to access this provider directly, try to prefer {@see self::statefulAuth()} or {@see self::statelessAuth()}.
     *
     * @return KeycloakProvider
     */
    public function oauthProvider(): KeycloakProvider
    {
        return $this->container->getKeycloakOauthProvider();
    }

    /**
     * Allows access to the user lists managed by the AuthClient.
     *
     * @return UserLayerInterface
     */
    public function users(): UserLayerInterface
    {
        return $this->container->getUserStorage();
    }

    /**
     * Returns the authorization layer for stateful authentication.
     * Stateful authentication means that the user will be logged in by the server and the tokens will be stored in the session.
     * The session handling is done by the {@see SessionAdapterInterface} implementation.
     *
     * Note, if you want to use stateful authentication, make sure that the session is started before doing the auth.
     *
     * @return StatefulAuth
     */
    public function statefulAuth(): StatefulAuth
    {
        return $this->container->getStatefulAuth();
    }

    /**
     * Returns the authorization layer for stateless authentication.
     * Stateless authentication means that the user will be logged in by an external client and the tokens will be passed to the server.
     * There is no session handling done by the server.
     *
     * @return StatelessAuth
     */
    public function statelessAuth(): StatelessAuth
    {
        return $this->container->getStatelessAuth();
    }

    /**
     * Guards allow you to check if a user has a specific permission or belongs to a specific group.
     * You can use either {@see StatefulAuth::getGuard()} or {@see StatelessAuth::getGuard()} to access the guard
     * of the currently authenticated user. This method allows you to access the guard of an arbitrary user.
     *
     * @return GuardLayerInterface
     */
    public function guard(): GuardLayerInterface
    {
        return $this->container->getGuardFactory();
    }

    /**
     * Allows access to the user groups managed by the AuthClient.
     *
     * @return GroupLayerInterface
     */
    public function groups(): GroupLayerInterface
    {
        return $this->container->getGroupStorage();
    }

    /**
     * Allows access to the user roles managed by the AuthClient.
     *
     * @return RoleLayerInterface
     */
    public function roles(): RoleLayerInterface
    {
        return $this->container->getRoleStorage();
    }

    /**
     * Allows modification of the user profile structure as well and a way to update the profile data of users.
     * Note, that both actions require the client to have additional permissions in Keycloak.
     *
     * @return ProfileLayerInterface
     */
    public function profile(): ProfileLayerInterface
    {
        return $this->container->getProfileLayer();
    }

    /**
     * Allows retrieval of resources and the management of user permissions on these resources.
     * Can also be used to add and remove resources for the client.
     * @return ResourceLayerInterface
     */
    public function resources(): ResourceLayerInterface
    {
        return $this->container->getResourceStorage();
    }
}
