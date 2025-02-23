<?php
declare(strict_types=1);


namespace Hawk\AuthClient\FrontendApi\Handler;


use Hawk\AuthClient\FrontendApi\Util\HandlerContext;
use Hawk\AuthClient\FrontendApi\Util\Request;
use Hawk\AuthClient\FrontendApi\Util\ResponseFactory;

class AuthenticationRefreshTokenHandler implements FrontendApiHandlerInterface
{
    public const string ROUTE = 'auth-refresh-token';

    #[\Override] public function canHandle(Request $request): bool
    {
        return $request->getRoute() === self::ROUTE;
    }

    #[\Override] public function handle(Request $request, ResponseFactory $responseFactory, HandlerContext $context): mixed
    {
        if (!$request->getPostValue('refreshToken')) {
            return $responseFactory->buildBadRequest('Missing "refreshToken" parameter');
        }

        try {
            $newToken = $context->getKeycloakProvider()->getAccessToken('refresh_token', [
                'refresh_token' => $request->getPostValue('refreshToken')
            ]);

            return $responseFactory->buildResponse([
                'token' => $newToken->getToken(),
                'refreshToken' => $newToken->getRefreshToken(),
                'expires' => $newToken->getExpires(),
                'idToken' => $newToken->getValues()['id_token'] ?? '',
            ]);
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), 'Session not active')) {
                return $responseFactory->buildUnauthorized('Session not active');
            }

            return $responseFactory->buildBadRequest($e->getMessage());
        }
    }
}
