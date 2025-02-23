<?php
declare(strict_types=1);


namespace Hawk\AuthClient\FrontendApi\Handler;


use Hawk\AuthClient\FrontendApi\Util\HandlerContext;
use Hawk\AuthClient\FrontendApi\Util\Request;
use Hawk\AuthClient\FrontendApi\Util\ResponseFactory;
use Hawk\AuthClient\Users\Value\User;

class UserProfileHandler implements FrontendApiHandlerInterface
{
    public const string ROUTE = 'user-profile';

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

        $profile = $user->getProfile();
        $raw = $profile->jsonSerialize();

        $raw['structure']['attributesLocal'] = [];
        foreach ($profile->getStructure()->getFieldsWithGlobals() as $fieldDef) {
            $raw['structure']['attributesLocal'][$fieldDef->getName()] = $fieldDef->getFullName();
        }

        $raw['structure']['groupsLocal'] = [];
        foreach ($profile->getStructure()->getGroupsWithGlobals() as $group) {
            $raw['structure']['groupsLocal'][$group->getName()] = $group->getFullName();
        }

        return $responseFactory->buildCacheableResponse($raw);
    }
}
