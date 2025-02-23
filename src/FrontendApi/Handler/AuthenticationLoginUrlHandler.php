<?php
declare(strict_types=1);


namespace Hawk\AuthClient\FrontendApi\Handler;


use Hawk\AuthClient\FrontendApi\Util\HandlerContext;
use Hawk\AuthClient\FrontendApi\Util\Request;
use Hawk\AuthClient\FrontendApi\Util\ResponseFactory;

class AuthenticationLoginUrlHandler implements FrontendApiHandlerInterface
{
    public const string ROUTE = 'auth-login-url';

    #[\Override] public function canHandle(Request $request): bool
    {
        return $request->getRoute() === self::ROUTE;
    }

    #[\Override] public function handle(Request $request, ResponseFactory $responseFactory, HandlerContext $context): mixed
    {
        if (empty($request->getPostValue('redirectUrl'))) {
            return $responseFactory->buildBadRequest('Missing "redirectUrl" parameter');
        }

        $options = ['redirect_uri' => $request->getPostValue('redirectUrl')];
        if (!empty($request->getPostValue('state'))) {
            $options['state'] = $request->getPostValue('state');
        }

        if (!empty($request->getPostValue('codeChallenge'))) {
            $options['code_challenge'] = $request->getPostValue('codeChallenge');
            $options['code_challenge_method'] = 'S256';
        }

        return $responseFactory->buildResponse(['url' => $context->getKeycloakProvider()->getAuthorizationUrl($options)]);
    }
}
