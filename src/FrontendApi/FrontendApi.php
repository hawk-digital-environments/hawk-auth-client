<?php
declare(strict_types=1);


namespace Hawk\AuthClient\FrontendApi;


use Hawk\AuthClient\FrontendApi\Handler\AuthenticationExchangeCodeForTokenHandler;
use Hawk\AuthClient\FrontendApi\Handler\AuthenticationLoginUrlHandler;
use Hawk\AuthClient\FrontendApi\Handler\AuthenticationLogoutUrlHandler;
use Hawk\AuthClient\FrontendApi\Handler\AuthenticationRefreshTokenHandler;
use Hawk\AuthClient\FrontendApi\Handler\PermissionsHandler;
use Hawk\AuthClient\FrontendApi\Handler\UserInfoHandler;
use Hawk\AuthClient\FrontendApi\Handler\UserProfileHandler;
use Hawk\AuthClient\FrontendApi\Util\HandlerContext;
use Hawk\AuthClient\FrontendApi\Util\HandlerStack;
use Hawk\AuthClient\FrontendApi\Util\ResponseFactory;
use Hawk\AuthClient\Permissions\Guard;
use Hawk\AuthClient\Request\RequestAdapterInterface;

class FrontendApi
{
    public const string API_ROUTE_QUERY_PARAMETER = 'hawk-api-route';

    protected HandlerContext $context;
    protected RequestAdapterInterface $requestAdapter;
    protected ResponseFactory $responseFactory;
    protected HandlerStack $handlerStack;

    public function __construct(
        HandlerContext          $context,
        RequestAdapterInterface $requestAdapter,
        ResponseFactory         $responseFactory,
        HandlerStack            $handlerStack
    )
    {
        $this->context = $context;
        $this->requestAdapter = $requestAdapter;
        $this->responseFactory = $responseFactory;
        $this->handlerStack = $handlerStack;

        $handlerStack->addHandler(new AuthenticationLoginUrlHandler());
        $handlerStack->addHandler(new AuthenticationExchangeCodeForTokenHandler());
        $handlerStack->addHandler(new AuthenticationRefreshTokenHandler());
        $handlerStack->addHandler(new AuthenticationLogoutUrlHandler());
    }

    /**
     * By default, the responses are generated using {@see self::defaultResponseFactory()} which is a
     * bare-bones JSON response factory. You can override this method to provide a custom response factory.
     * This is useful if you want to integrate the library with a framework or a different response format.
     *
     * @param callable(array, array, int): mixed $responseFactory A callable that generates the response.
     *                                            The response should ALWAYS convert the data to JSON and set the headers.
     *                                            - array $data The data in the response, which is an associative array to convert to JSON
     *                                            - array $headers A list of headers to set. The list is an associative array with the header name as the key and the header value as the value
     *                                            - int $statusCode The status code to set
     *                                            The factory MAY return a value, that will be returned by the {@see handle()} method.
     *
     * @return $this
     */
    public function setResponseFactory(callable $responseFactory): static
    {
        $this->responseFactory->setConcreteFactory($responseFactory);
        return $this;
    }

    /**
     * Normally, when the {@see self::API_ROUTE_QUERY_PARAMETER} is not present in the query of the request,
     * the handler will respond with an error. If you set this to true, the handler will return null instead.
     * This allows for configurations where the frontend api is handled in combination with other api
     *
     * @param bool $state
     * @return $this
     */
    public function setFallthroughAllowed(bool $state = true): static
    {
        $this->handlerStack->setFallthroughAllowed($state);
        return $this;
    }

    /**
     * Call this method to allow the frontend api to handle user info requests.
     * This enables the {@see UserInfoHandler::ROUTE} that provides information about the current user, similar
     * to the OAuth2 userinfo endpoint. The user information is only available if the user is authenticated.
     *
     * @return $this
     */
    public function enableUserInfo(): static
    {
        $this->handlerStack->addHandler(new UserInfoHandler());
        return $this;
    }

    /**
     * Call this method to allow the frontend api to handle user profile requests.
     * This enables the {@see UserProfileHandler::ROUTE} that provides information about the current user's profile.
     * The user profile is only available if the user is authenticated.
     *
     * IMPORTANT: While the profile gives you access to ALL user data, it is a performance hit to load it every time.
     * If you know which attributes you need, you should create a custom "claim" for them that will be added to the "user-info" endpoint.
     * Those claims will be automatically available in the {@see UserInfoHandler::ROUTE} endpoint.
     *
     * @return $this
     */
    public function enableUserProfile(): static
    {
        $this->handlerStack->addHandler(new UserProfileHandler());
        return $this;
    }

    /**
     * Call this method to allow the frontend api to handle permissions requests.
     * This api allows the js library to do the same actions as {@see Guard} in the backend.
     *
     * Note, that enabling this feature, will also enable the {@see self::enableUserInfo()} feature.
     *
     * @return $this
     */
    public function enablePermissions(): static
    {
        $this->enableUserInfo();
        $this->handlerStack->addHandler(new PermissionsHandler());
        return $this;
    }

    /**
     * This method does the main work of routing the request to the correct handler.
     *
     * It will automatically handle the ETag caching for you, based on the {@see HandlerContext::getCacheBuster()} method.
     * This allows the JS library to cache the responses and only request new data if the cache buster changes.
     *
     * @return mixed This is normally the response of {@see self::setResponseFactory()}, based on the data
     *               generated by the handler. If the {@see self::API_ROUTE_QUERY_PARAMETER} is missing and
     *               {@see self::setFallthroughAllowed()} is set to true, this method will return null.
     *               If the route is not found, the method will return a 404 response.
     *               If {@see self::setFallthroughAllowed()} is not set (default), and the {@see self::API_ROUTE_QUERY_PARAMETER}
     *               is missing, the method will return a 400 response.
     *               Note: Depending on your response factory, this method may exit the script.
     */
    public function handle(): mixed
    {
        return $this->handlerStack->handle(
            $this->requestAdapter,
            $this->responseFactory,
            $this->context
        );
    }
}
