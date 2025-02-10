<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Users\Value;


use Hawk\AuthClient\Groups\Value\GroupList;
use Hawk\AuthClient\Groups\Value\GroupReferenceList;
use Hawk\AuthClient\Profiles\Value\UserProfile;
use Hawk\AuthClient\Roles\Value\RoleList;
use Hawk\AuthClient\Roles\Value\RoleReferenceList;
use Hawk\AuthClient\Users\Value\User;
use Hawk\AuthClient\Users\Value\UserClaims;
use Hawk\AuthClient\Users\Value\UserContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(User::class)]
class UserTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new User(
            '83335934-fc49-4c59-8199-de47c3d03ac5',
            'service-account-clientId',
            $this->createStub(UserClaims::class),
            $this->createStub(RoleReferenceList::class),
            $this->createStub(GroupReferenceList::class),
            $this->createStub(UserContext::class)
        );
        $this->assertInstanceOf(User::class, $sut);
    }

    public function testItCanReturnTheValues(): void
    {
        $claims = $this->createStub(UserClaims::class);
        $roleReferenceList = $this->createStub(RoleReferenceList::class);
        $groupReferenceList = $this->createStub(GroupReferenceList::class);
        $groupList = $this->createStub(GroupList::class);
        $roleList = $this->createStub(RoleList::class);
        $profile = $this->createStub(UserProfile::class);
        $context = $this->createMock(UserContext::class);
        $context->method('getGroups')->with($groupReferenceList)->willReturn($groupList);
        $context->method('getRoles')->with($roleReferenceList)->willReturn($roleList);
        $context->method('getProfile')->with($this->isInstanceOf(User::class))->willReturn($profile);
        $sut = new User(
            '83335934-fc49-4c59-8199-de47c3d03ac5',
            'service-account-clientId',
            $claims,
            $roleReferenceList,
            $groupReferenceList,
            $context
        );

        $this->assertSame('83335934-fc49-4c59-8199-de47c3d03ac5', $sut->getId());
        $this->assertSame('service-account-clientId', $sut->getUsername());
        $this->assertSame($claims, $sut->getClaims());
        $this->assertSame($roleReferenceList, $sut->getRoleReferences());
        $this->assertSame($roleList, $sut->getRoles());
        $this->assertSame($groupReferenceList, $sut->getGroupReferences());
        $this->assertSame($groupList, $sut->getGroups());
        $this->assertSame($profile, $sut->getProfile());
    }

    public function testItCanBeConvertedIntoAnArray(): void
    {
        $claims = $this->createStub(UserClaims::class);
        $claims->method('jsonSerialize')->willReturn(['claim' => 'value']);
        $roleReferenceList = $this->createStub(RoleReferenceList::class);
        $roleReferenceList->method('jsonSerialize')->willReturn(['role']);
        $groupReferenceList = $this->createStub(GroupReferenceList::class);
        $groupReferenceList->method('jsonSerialize')->willReturn(['group']);
        $context = $this->createMock(UserContext::class);
        $sut = new User(
            '83335934-fc49-4c59-8199-de47c3d03ac5',
            'service-account-clientId',
            $claims,
            $roleReferenceList,
            $groupReferenceList,
            $context
        );

        $expected = [
            User::ARRAY_KEY_ID => '83335934-fc49-4c59-8199-de47c3d03ac5',
            User::ARRAY_KEY_USERNAME => 'service-account-clientId',
            User::ARRAY_KEY_CLAIMS => ['claim' => 'value'],
            User::ARRAY_KEY_ROLES => ['role'],
            User::ARRAY_KEY_GROUPS => ['group'],
        ];

        $this->assertSame($expected, $sut->toArray());
        $this->assertSame($expected, $sut->jsonSerialize());
    }

}
