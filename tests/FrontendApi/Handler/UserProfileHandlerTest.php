<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\FrontendApi\Handler;


use Hawk\AuthClient\FrontendApi\Handler\UserProfileHandler;
use Hawk\AuthClient\FrontendApi\Util\CurrentUserFinder;
use Hawk\AuthClient\FrontendApi\Util\HandlerContext;
use Hawk\AuthClient\FrontendApi\Util\Request;
use Hawk\AuthClient\FrontendApi\Util\ResponseFactory;
use Hawk\AuthClient\Profiles\Structure\ProfileField;
use Hawk\AuthClient\Profiles\Structure\ProfileGroup;
use Hawk\AuthClient\Profiles\Structure\ProfileStructure;
use Hawk\AuthClient\Profiles\Value\UserProfile;
use Hawk\AuthClient\Users\Value\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UserProfileHandler::class)]
class UserProfileHandlerTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new UserProfileHandler();
        $this->assertInstanceOf(UserProfileHandler::class, $sut);
    }

    public function testItDetectsIfItCanHandle(): void
    {
        $sut = new UserProfileHandler();
        $request = $this->createStub(Request::class);
        $request->method('getRoute')->willReturn(UserProfileHandler::ROUTE);
        $this->assertTrue($sut->canHandle($request));

        $request = $this->createStub(Request::class);
        $request->method('getRoute')->willReturn('user-profiles');
        $this->assertFalse($sut->canHandle($request));
    }

    public function testItReturnsResponseIfUserCouldNotBeFound(): void
    {
        $request = $this->createStub(Request::class);
        $responseFactory = $this->createMock(ResponseFactory::class);
        $responseFactory->expects($this->never())->method('buildCacheableResponse');
        $context = $this->createStub(HandlerContext::class);

        $response = new \stdClass();
        $userFinder = $this->createMock(CurrentUserFinder::class);
        $userFinder->expects($this->once())->method('findUser')->with($request, $responseFactory)->willReturn($response);
        $context->method('getCurrentUserFinder')->willReturn($userFinder);

        $sut = new UserProfileHandler();
        $this->assertSame($response, $sut->handle($request, $responseFactory, $context));
    }

    public function testItBuildsProfileForUser(): void
    {
        $globalField = $this->createStub(ProfileField::class);
        $globalField->method('getName')->willReturn('global-field');
        $globalField->method('getFullName')->willReturn('global-field');

        $clientField = $this->createStub(ProfileField::class);
        $clientField->method('getName')->willReturn('client-field');
        $clientField->method('getFullName')->willReturn('hawk.client1.client-field');

        $globalGroup = $this->createStub(ProfileGroup::class);
        $globalGroup->method('getName')->willReturn('global-group');
        $globalGroup->method('getFullName')->willReturn('global-group');

        $clientGroup = $this->createStub(ProfileGroup::class);
        $clientGroup->method('getName')->willReturn('client-group');
        $clientGroup->method('getFullName')->willReturn('hawk.client1.client-group');

        $structure = $this->createStub(ProfileStructure::class);
        $structure->method('getFieldsWithGlobals')->willReturn([$globalField, $clientField]);
        $structure->method('getGroupsWithGlobals')->willReturn([$globalGroup, $clientGroup]);

        $profile = $this->createStub(UserProfile::class);
        $profile->method('getStructure')->willReturn($structure);
        $profile->method('jsonSerialize')->willReturn(['user' => 'foo-bar']);

        $user = $this->createStub(User::class);
        $user->method('getProfile')->willReturn($profile);

        $request = $this->createStub(Request::class);
        $response = new \stdClass();
        $responseFactory = $this->createMock(ResponseFactory::class);
        $responseFactory->expects($this->once())->method('buildCacheableResponse')->with([
            'user' => 'foo-bar',
            'structure' => [
                'attributesLocal' => [
                    'global-field' => 'global-field',
                    'client-field' => 'hawk.client1.client-field'
                ],
                'groupsLocal' => [
                    'global-group' => 'global-group',
                    'client-group' => 'hawk.client1.client-group'
                ]
            ]
        ])->willReturn($response);
        $context = $this->createStub(HandlerContext::class);

        $userFinder = $this->createMock(CurrentUserFinder::class);
        $userFinder->expects($this->once())->method('findUser')->with($request, $responseFactory)->willReturn($user);
        $context->method('getCurrentUserFinder')->willReturn($userFinder);

        $sut = new UserProfileHandler();

        $this->assertSame($response, $sut->handle($request, $responseFactory, $context));
    }

}
