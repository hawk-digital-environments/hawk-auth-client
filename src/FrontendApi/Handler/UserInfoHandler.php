<?php
declare(strict_types=1);


namespace Hawk\AuthClient\FrontendApi\Handler;


use Hawk\AuthClient\FrontendApi\Util\HandlerContext;
use Hawk\AuthClient\FrontendApi\Util\Request;
use Hawk\AuthClient\FrontendApi\Util\ResponseFactory;
use Hawk\AuthClient\Users\Value\User;

class UserInfoHandler implements FrontendApiHandlerInterface
{
    public const string ROUTE = 'user-info';

    #[\Override] public function canHandle(Request $request): bool
    {
        return $request->getRoute() === self::ROUTE;
    }

    #[\Override] public function handle(Request $request, ResponseFactory $responseFactory, HandlerContext $context): mixed
    {
        $user = $context->getCurrentUserFinder()->findUser($request, $responseFactory);

        if (!$user instanceof User) {
            return $user;
        }

        return $responseFactory->buildCacheableResponse($user->toArray());
    }
}
