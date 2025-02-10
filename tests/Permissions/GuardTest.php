<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Permissions;


use Hawk\AuthClient\Groups\Value\GroupReferenceList;
use Hawk\AuthClient\Permissions\Guard;
use Hawk\AuthClient\Permissions\PermissionStorage;
use Hawk\AuthClient\Resources\Value\Resource;
use Hawk\AuthClient\Resources\Value\ResourceScopes;
use Hawk\AuthClient\Roles\Value\RoleReferenceList;
use Hawk\AuthClient\Users\Value\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Guard::class)]
class GuardTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new Guard($this->createStub(User::class), $this->createStub(PermissionStorage::class));
        $this->assertInstanceOf(Guard::class, $sut);
    }

    public function testItCanCheckForAnyRole(): void
    {
        $roleReferences = $this->createMock(RoleReferenceList::class);
        $roleReferences->expects($this->once())->method('hasAny')->with('foo', 'bar')->willReturn(true);
        $user = $this->createStub(User::class);
        $user->method('getRoleReferences')->willReturn($roleReferences);
        $sut = new Guard($user, $this->createStub(PermissionStorage::class));

        $this->assertTrue($sut->hasAnyRole('foo', 'bar'));
    }

    public function testItCanCheckForAnyGroup(): void
    {
        $groupReferences = $this->createMock(GroupReferenceList::class);
        $groupReferences->expects($this->once())->method('hasAny')->with('foo', 'bar')->willReturn(true);
        $user = $this->createStub(User::class);
        $user->method('getGroupReferences')->willReturn($groupReferences);
        $sut = new Guard($user, $this->createStub(PermissionStorage::class));

        $this->assertTrue($sut->hasAnyGroup('foo', 'bar'));
    }

    public function testItCanCheckForAnyGroupOrChildOfGroup(): void
    {
        $groupReferences = $this->createMock(GroupReferenceList::class);
        $groupReferences->expects($this->once())->method('hasAnyOrHasChildOfAny')->with('foo', 'bar')->willReturn(true);
        $user = $this->createStub(User::class);
        $user->method('getGroupReferences')->willReturn($groupReferences);
        $sut = new Guard($user, $this->createStub(PermissionStorage::class));

        $this->assertTrue($sut->hasAnyGroupOrHasChildOfAny('foo', 'bar'));
    }

    public function testItCanCheckForResourceScopesWithoutGranted(): void
    {
        $resource = $this->createStub(Resource::class);
        $user = $this->createStub(User::class);
        $permissions = $this->createMock(PermissionStorage::class);
        $permissions->method('getGrantedResourceScopes')->with($resource, $user)->willReturn(null);
        $sut = new Guard($user, $permissions);

        $this->assertFalse($sut->hasAnyResourceScope($resource, 'foo', 'bar'));
        $this->assertFalse($sut->hasAllResourceScopes($resource, 'foo', 'bar'));
    }

    public function testItCanCheckForResourceScopesWithGranted(): void
    {
        $scopes = new ResourceScopes('foo', 'bar');
        $resource = $this->createStub(Resource::class);
        $user = $this->createStub(User::class);
        $permissions = $this->createMock(PermissionStorage::class);
        $permissions->method('getGrantedResourceScopes')->with($resource, $user)->willReturn($scopes);
        $sut = new Guard($user, $permissions);

        $this->assertTrue($sut->hasAnyResourceScope($resource, 'foo', 'baz'));
        $this->assertTrue($sut->hasAnyResourceScope($resource, 'foo'));
        $this->assertFalse($sut->hasAnyResourceScope($resource, 'baz'));
        $this->assertTrue($sut->hasAnyResourceScope($resource));

        $this->assertTrue($sut->hasAllResourceScopes($resource, 'foo', 'bar'));
        $this->assertFalse($sut->hasAllResourceScopes($resource, 'foo', 'baz'));
        $this->assertTrue($sut->hasAllResourceScopes($resource, 'foo'));
        $this->assertFalse($sut->hasAllResourceScopes($resource));
    }

}
