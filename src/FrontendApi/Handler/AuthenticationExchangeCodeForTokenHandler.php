<?php
declare(strict_types=1);


namespace Hawk\AuthClient\FrontendApi\Handler;


use Hawk\AuthClient\FrontendApi\Util\HandlerContext;
use Hawk\AuthClient\FrontendApi\Util\Request;
use Hawk\AuthClient\FrontendApi\Util\ResponseFactory;

class AuthenticationExchangeCodeForTokenHandler implements FrontendApiHandlerInterface
{
    public const string ROUTE = 'auth-exchange-code-for-token';

    #[\Override] public function canHandle(Request $request): bool
    {
        return $request->getRoute() === self::ROUTE;
    }

    #[\Override] public function handle(Request $request, ResponseFactory $responseFactory, HandlerContext $context): mixed
    {
        if (empty($request->getPostValue('redirectUrl'))) {
            return $responseFactory->buildBadRequest('Missing "redirectUrl" parameter');
        }

        if (empty($request->getPostValue('code'))) {
            return $responseFactory->buildBadRequest('Missing "code" parameter');
        }

        $options = [
            'code' => $request->getPostValue('code'),
            'redirect_uri' => $request->getPostValue('redirectUrl')
        ];

        if (!empty($request->getPostValue('codeVerifier'))) {
            $options['code_verifier'] = $request->getPostValue('codeVerifier');
        }

        try {
            $token = $context->getKeycloakProvider()->getAccessToken('authorization_code', $options);

            return $responseFactory->buildResponse([
                'token' => $token->getToken(),
                'refreshToken' => $token->getRefreshToken(),
                'idToken' => $token->getValues()['id_token'] ?? '',
                'expires' => $token->getExpires(),
            ]);
        } catch (\Exception $e) {
            return $responseFactory->buildBadRequest($e->getMessage());
        }
    }
}
