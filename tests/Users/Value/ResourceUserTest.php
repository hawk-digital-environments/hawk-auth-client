<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Users\Value;


use Hawk\AuthClient\Groups\Value\GroupReferenceList;
use Hawk\AuthClient\Resources\Value\ResourceScopes;
use Hawk\AuthClient\Roles\Value\RoleReferenceList;
use Hawk\AuthClient\Users\Value\ResourceUser;
use Hawk\AuthClient\Users\Value\User;
use Hawk\AuthClient\Users\Value\UserClaims;
use Hawk\AuthClient\Users\Value\UserContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ResourceUser::class)]
class ResourceUserTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new ResourceUser(
            '83335934-fc49-4c59-8199-de47c3d03ac5',
            'service-account-clientId',
            $this->createStub(UserClaims::class),
            $this->createStub(RoleReferenceList::class),
            $this->createStub(GroupReferenceList::class),
            $this->createStub(UserContext::class),
            new ResourceScopes('scope1', 'scope2')
        );
        $this->assertInstanceOf(ResourceUser::class, $sut);
    }

    public function testItCanReturnTheScopes(): void
    {
        $scopes = new ResourceScopes('scope1', 'scope2');
        $sut = new ResourceUser(
            '83335934-fc49-4c59-8199-de47c3d03ac5',
            'service-account-clientId',
            $this->createStub(UserClaims::class),
            $this->createStub(RoleReferenceList::class),
            $this->createStub(GroupReferenceList::class),
            $this->createStub(UserContext::class),
            $scopes
        );
        $this->assertSame($scopes, $sut->getScopes());
    }

    public function testItCanJsonSerialize(): void
    {
        $claims = $this->createStub(UserClaims::class);
        $claims->method('jsonSerialize')->willReturn(['claim' => 'value']);
        $roleReferenceList = $this->createStub(RoleReferenceList::class);
        $roleReferenceList->method('jsonSerialize')->willReturn(['role']);
        $groupReferenceList = $this->createStub(GroupReferenceList::class);
        $groupReferenceList->method('jsonSerialize')->willReturn(['group']);
        $context = $this->createMock(UserContext::class);
        $sut = new ResourceUser(
            '83335934-fc49-4c59-8199-de47c3d03ac5',
            'service-account-clientId',
            $claims,
            $roleReferenceList,
            $groupReferenceList,
            $context,
            new ResourceScopes('scope1', 'scope2')
        );

        $expected = [
            User::ARRAY_KEY_ID => '83335934-fc49-4c59-8199-de47c3d03ac5',
            User::ARRAY_KEY_USERNAME => 'service-account-clientId',
            User::ARRAY_KEY_CLAIMS => ['claim' => 'value'],
            User::ARRAY_KEY_ROLES => ['role'],
            User::ARRAY_KEY_GROUPS => ['group'],
            'scopes' => ['scope1', 'scope2']
        ];

        $this->assertSame($expected, $sut->toArray());
        $this->assertSame($expected, $sut->jsonSerialize());
    }

    public function testItCanBeCreatedFromUserAndScopes(): void
    {
        $sut = new ResourceUser(
            '83335934-fc49-4c59-8199-de47c3d03ac5',
            'service-account-clientId',
            $this->createStub(UserClaims::class),
            $this->createStub(RoleReferenceList::class),
            $this->createStub(GroupReferenceList::class),
            $this->createStub(UserContext::class),
            new ResourceScopes('scope1', 'scope2')
        );

        $created = ResourceUser::fromUserAndScopes($sut, $sut->getScopes());
        $this->assertNotSame($sut, $created);
        $this->assertEquals($sut, $created);
    }

}
