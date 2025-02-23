<?php
declare(strict_types=1);


namespace Hawk\AuthClient\FrontendApi\Handler;


use Hawk\AuthClient\FrontendApi\Util\HandlerContext;
use Hawk\AuthClient\FrontendApi\Util\Request;
use Hawk\AuthClient\FrontendApi\Util\ResponseFactory;
use League\OAuth2\Client\Token\AccessToken;

class AuthenticationLogoutUrlHandler implements FrontendApiHandlerInterface
{
    public const string ROUTE = 'auth-logout-url';

    #[\Override] public function canHandle(Request $request): bool
    {
        return $request->getRoute() === self::ROUTE;
    }

    #[\Override] public function handle(Request $request, ResponseFactory $responseFactory, HandlerContext $context): mixed
    {
        if (empty($request->getPostValue('redirectUrl'))) {
            return $responseFactory->buildBadRequest('Missing "redirectUrl" parameter');
        }

        if (empty($request->getPostValue('idToken'))) {
            return $responseFactory->buildBadRequest('Missing "idToken" parameter');
        }

        $token = new AccessToken([
            'access_token' => $request->getPostValue('idToken'),
            'id_token' => $request->getPostValue('idToken')
        ]);

        return $responseFactory->buildResponse([
            'url' => $context->getKeycloakProvider()->getLogoutUrl($token, $request->getPostValue('redirectUrl'))
        ]);
    }
}
