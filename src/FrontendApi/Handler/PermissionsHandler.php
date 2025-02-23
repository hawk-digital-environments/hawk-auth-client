<?php
declare(strict_types=1);


namespace Hawk\AuthClient\FrontendApi\Handler;


use Hawk\AuthClient\FrontendApi\Util\HandlerContext;
use Hawk\AuthClient\FrontendApi\Util\Request;
use Hawk\AuthClient\FrontendApi\Util\ResponseFactory;
use League\OAuth2\Client\Token\AccessToken;

class PermissionsHandler implements FrontendApiHandlerInterface
{
    public const string ROUTE = 'permissions';

    #[\Override] public function canHandle(Request $request): bool
    {
        return $request->getRoute() === self::ROUTE;
    }

    #[\Override] public function handle(Request $request, ResponseFactory $responseFactory, HandlerContext $context): mixed
    {
        $token = $request->getBearerToken();
        if (empty($token)) {
            return $responseFactory->buildUnauthorized();
        }

        $resourceId = $request->getQueryValue('resource');
        if (empty($resourceId)) {
            return $responseFactory->buildBadRequest('Invalid resource ID');
        }

        try {
            $token = new AccessToken([
                'access_token' => $token,
            ]);

            $user = $context->getUserStorage()->getOneByToken($token, fn() => $context->getKeycloakProvider()->getResourceOwner($token));
            if ($user === null) {
                return $responseFactory->buildUnauthorized();
            }
        } catch (\Throwable $e) {
            return $responseFactory->buildUnauthorized($e->getMessage());
        }

        $scopes = $context->getPermissionStorage()->getGrantedResourceScopes($resourceId, $user);

        return $responseFactory->buildResponse(['scopes' => $scopes]);
    }
}
