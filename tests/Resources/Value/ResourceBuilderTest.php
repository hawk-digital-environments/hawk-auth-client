<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Resources\Value;


use Hawk\AuthClient\Exception\NoResourceIdAssignedException;
use Hawk\AuthClient\Exception\NoResourceOwnerAssignedException;
use Hawk\AuthClient\Exception\ResourceNameMissingWhenCreatingResourceBuilderException;
use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Resources\ResourceCache;
use Hawk\AuthClient\Resources\Value\Resource;
use Hawk\AuthClient\Resources\Value\ResourceBuilder;
use Hawk\AuthClient\Resources\Value\ResourceScopes;
use Hawk\AuthClient\Users\UserStorage;
use Hawk\AuthClient\Users\Value\User;
use Hawk\AuthClient\Util\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ResourceBuilder::class)]
#[CoversClass(Resource::class)]
#[CoversClass(ResourceNameMissingWhenCreatingResourceBuilderException::class)]
#[CoversClass(NoResourceIdAssignedException::class)]
#[CoversClass(NoResourceOwnerAssignedException::class)]
class ResourceBuilderTest extends TestCase
{
    public function testItConstructsWithoutExistingResource(): void
    {
        $this->assertInstanceOf(ResourceBuilder::class, $this->createSutWithoutResource());
    }

    public function testItFailsToConstructWithoutExistingResourceAndResourceName(): void
    {
        $this->expectException(ResourceNameMissingWhenCreatingResourceBuilderException::class);
        $sut = new ResourceBuilder(
            null,
            $this->createStub(UserStorage::class),
            null,
            $this->createStub(KeycloakApiClient::class),
            $this->createStub(ResourceCache::class)
        );
        $this->assertInstanceOf(ResourceBuilder::class, $sut);
    }

    public function testItConstructsWithExistingResource(): void
    {
        $this->assertInstanceOf(ResourceBuilder::class, $this->createSutWithResource());
    }

    public function testItFailsToGetIdIfCreatedWithoutResource(): void
    {
        $this->expectException(NoResourceIdAssignedException::class);
        $this->createSutWithoutResource()->getId();
    }

    public function testItGetsTheIdIfCreatedWithResource(): void
    {
        $this->assertEquals('83335934-fc49-4c59-8199-de47c3d03ac5', $this->createSutWithResource()->getId());
    }

    public function testItDetectsIfItHasBeenCreatedWithOrWithoutResource(): void
    {
        $this->assertTrue($this->createSutWithResource()->doesUpdateExistingResource());
        $this->assertFalse($this->createSutWithoutResource()->doesUpdateExistingResource());
    }

    public function testItCanGetAndSetTheName(): void
    {
        foreach ([
                     $this->createSutWithoutResource(true),
                     $this->createSutWithResource(true),
                 ] as $sut) {
            /** @var ResourceBuilder $sut */
            $this->assertEquals('my-resource', $sut->getName());
            $sut->setName('new-name');
            $this->assertEquals('new-name', $sut->getName());
            $sut->save();
            // Save twice, to check if "dirty" has been reset (Otherwise it would throw an assertion exception)
            $sut->save();
        }

        // Do nothing if no changes have been made
        foreach ([
                     $this->createSutWithoutResource(true),
                     $this->createSutWithResource(false),
                 ] as $sut) {
            /** @var ResourceBuilder $sut */
            $this->assertEquals('my-resource', $sut->getName());
            $sut->setName('my-resource');
            $sut->save();
            $sut->save();
        }
    }

    public function testItCanGetAndSetTheDisplayName(): void
    {
        $expectedDisplayNames = ['my-resource', 'displayName'];
        foreach ([
                     $this->createSutWithoutResource(true),
                     $this->createSutWithResource(true),
                 ] as $k => $sut) {
            /** @var ResourceBuilder $sut */
            $this->assertEquals($expectedDisplayNames[$k], $sut->getDisplayName());
            $sut->setDisplayName('new-display-name');
            $this->assertEquals('new-display-name', $sut->getDisplayName());
            $sut->save();
            // Save twice, to check if "dirty" has been reset (Otherwise it would throw an assertion exception)
            $sut->save();
        }

        // Do nothing if no changes have been made
        foreach ([
                     $this->createSutWithoutResource(true),
                     $this->createSutWithResource(false),
                 ] as $k => $sut) {
            /** @var ResourceBuilder $sut */
            $this->assertEquals($expectedDisplayNames[$k], $sut->getDisplayName());
            $sut->setDisplayName($expectedDisplayNames[$k]);
            $sut->save();
            $sut->save();
        }
    }

    public function testItFailsToGetTheOwnerIfNotYetAssigned(): void
    {
        $this->expectException(NoResourceOwnerAssignedException::class);
        $this->createSutWithoutResource()->getOwner();
    }

    public function testItCanSetAndGetTheOwnerWithoutResource(): void
    {
        $owner = $this->createStub(User::class);
        $ownerId = new Uuid('83335934-fc49-4c59-8199-de47c3d03ac3');
        $owner->method('getId')->willReturn($ownerId);

        $sut = $this->createSutWithoutResource(true, owner: $owner);
        $this->assertFalse($sut->isUserManaged());
        $sut->setOwner($owner);
        $this->assertTrue($sut->isUserManaged());
        $this->assertSame($owner, $sut->getOwner());
        $sut->save();
    }

    public function testItCanSetAndGetTheOwnerWithResource(): void
    {
        $originalOwner = $this->createStub(User::class);
        $originalOwnerId = new Uuid('83335934-fc49-4c59-8199-de47c3d03ac3');
        $originalOwner->method('getId')->willReturn($originalOwnerId);

        $newOwner = $this->createStub(User::class);
        $newOwnerId = new Uuid('83335934-fc49-4c59-8199-de47c3d03ac4');
        $newOwner->method('getId')->willReturn($newOwnerId);

        $sut = $this->createSutWithResource(true, owner: [
            [$originalOwnerId, $originalOwner],
            [$newOwnerId, $newOwner]
        ]);
        $this->assertTrue($sut->isUserManaged());
        $this->assertSame($originalOwner, $sut->getOwner());
        $sut->setOwner($newOwner);
        $this->assertTrue($sut->isUserManaged());
        $this->assertSame($newOwner, $sut->getOwner());
        $sut->save();
        $sut->save();
    }

    public function testItCanManageAttributes(): void
    {
        foreach ([
                     $this->createSutWithoutResource(true),
                     $this->createSutWithResource(true),
                 ] as $sut) {
            /** @var ResourceBuilder $sut */
            $this->assertEmpty($sut->getAttributes());
            $this->assertNull($sut->getAttribute('key'));
            $sut->setAttribute('key', 'value');
            $sut->setAttribute('key2', 'value2');
            $this->assertEquals(['key' => 'value', 'key2' => 'value2'], $sut->getAttributes());
            $sut->removeAttribute('key2');
            $this->assertEquals('value', $sut->getAttribute('key'));
            $sut->save();
            $sut->setAttribute('key', 'value');
            $sut->removeAttribute('key2');
            // Not saved again, because no change, otherwise the cache mock will throw here
            $sut->save();
        }
    }

    public function testItCanSetAndGetTheIconUri(): void
    {
        foreach ([
                     $this->createSutWithoutResource(true),
                     $this->createSutWithResource(true),
                 ] as $sut) {
            /** @var ResourceBuilder $sut */
            $this->assertNull($sut->getIconUri());
            $sut->setIconUri('http://example.com/icon.png');
            $this->assertEquals('http://example.com/icon.png', $sut->getIconUri());
            $sut->save();
            $sut->setIconUri('http://example.com/icon.png');
            // Not saved again, because no change, otherwise the cache mock will throw here
            $sut->save();
        }
    }

    public function testItCanManageUris(): void
    {
        foreach ([
                     $this->createSutWithoutResource(true),
                     $this->createSutWithResource(true),
                 ] as $sut) {
            /** @var ResourceBuilder $sut */
            $this->assertEmpty($sut->getUris());
            $sut->addUri('http://example.com/uri1');
            $sut->addUri('http://example.com/uri2');
            $this->assertEquals(['http://example.com/uri1', 'http://example.com/uri2'], $sut->getUris());
            $sut->removeUri('http://example.com/uri2');
            $this->assertEquals(['http://example.com/uri1'], $sut->getUris());
            $sut->save();
            $sut->addUri('http://example.com/uri1');
            $sut->removeUri('http://example.com/uri2');
            // Not saved again, because no change, otherwise the cache mock will throw here
            $sut->save();
        }
    }

    public function testItCanManageScopes(): void
    {
        foreach ([
                     $this->createSutWithoutResource(true),
                     $this->createSutWithResource(true),
                 ] as $sut) {
            /** @var ResourceBuilder $sut */
            $this->assertEmpty($sut->getScopes());
            $sut->addScope('scope1');
            $sut->addScope('scope2');
            $this->assertEquals(new ResourceScopes('scope1', 'scope2'), $sut->getScopes());
            $sut->removeScope('scope2');
            $this->assertEquals(new ResourceScopes('scope1'), $sut->getScopes());
            $sut->save();
            $sut->addScope('scope1');
            $sut->removeScope('scope2');
            // Not saved again, because no change, otherwise the cache mock will throw here
            $sut->save();
        }
    }

    public function testItCanSetAndGetType(): void
    {
        foreach ([
                     $this->createSutWithoutResource(true),
                     $this->createSutWithResource(true),
                 ] as $sut) {
            /** @var ResourceBuilder $sut */
            $this->assertNull($sut->getType());
            $sut->setType('type');
            $this->assertEquals('type', $sut->getType());
            $sut->save();
            $sut->setType('type');
            // Not saved again, because no change, otherwise the cache mock will throw here
            $sut->save();
        }
    }

    public function testItCanBeJsonEncodedWithoutResource(): void
    {
        $expected = [
            'name' => 'my-resource',
            'displayName' => 'my-resource',
            'ownerManagedAccess' => false,
            'attributes' => [],
            'icon_uri' => '',
            'uris' => [],
            'scopes' => [],
            'type' => ''
        ];
        $this->assertEquals($expected, $this->createSutWithoutResource()->jsonSerialize());

        $expected = [
            'name' => 'my-resource',
            'displayName' => 'display name',
            'ownerManagedAccess' => true,
            'attributes' => [
                'attr1' => [
                    'value1'
                ],
                'attr2' => [
                    'value2'
                ]
            ],
            'icon_uri' => 'http://example.com/icon.png',
            'uris' => ['uri1'],
            'scopes' => ['scope1', 'scope2'],
            'type' => 'my-type',
            'owner' => '83335934-fc49-4c59-8199-de47c3d03ac3'
        ];
        $sut = $this->createSutWithoutResource();
        $owner = $this->createStub(User::class);
        $owner->method('getId')->willReturn(new Uuid($expected['owner']));

        $sut->setDisplayName($expected['displayName']);
        $sut->setOwner($owner);
        $sut->setAttribute('attr1', 'value1');
        $sut->setAttribute('attr2', 'value2');
        $sut->setAttribute('attr3', 'value3');
        $sut->removeAttribute('attr3');
        $sut->setIconUri('http://example.com/icon.png');
        $sut->addUri('uri1');
        $sut->addUri('uri2');
        $sut->removeUri('uri2');
        $sut->addScope('scope1');
        $sut->addScope('scope2');
        $sut->addScope('scope3');
        $sut->removeScope('scope3');
        $sut->setType($expected['type']);
        $this->assertEquals($expected, $sut->jsonSerialize());
    }

    public function testItCanBeJsonEncodedWithResource(): void
    {
        $expected = [
            'name' => 'my-resource',
            'displayName' => 'displayName',
            'ownerManagedAccess' => false,
            'attributes' => [],
            'icon_uri' => '',
            'uris' => [],
            'scopes' => [],
            'type' => '',
        ];
        $this->assertEquals($expected, $this->createSutWithResource()->jsonSerialize());

        $expected = [
            'name' => 'other-resource',
            'displayName' => 'display name',
            'ownerManagedAccess' => true,
            'attributes' => [
                'attr1' => [
                    'value1'
                ],
                'attr2' => [
                    'value2'
                ]
            ],
            'icon_uri' => 'http://example.com/icon.png',
            'uris' => ['uri1'],
            'scopes' => ['scope1', 'scope2'],
            'type' => 'my-type',
            'owner' => '83335934-fc49-4c59-8199-de47c3d03ac5'
        ];

        $sut = $this->createSutWithResource();
        $owner = $this->createStub(User::class);
        $owner->method('getId')->willReturn(new Uuid($expected['owner']));

        $sut->setName($expected['name']);
        $sut->setDisplayName($expected['displayName']);
        $sut->setOwner($owner);
        $sut->setAttribute('attr1', 'value1');
        $sut->setAttribute('attr2', 'value2');
        $sut->setAttribute('attr3', 'value3');
        $sut->removeAttribute('attr3');
        $sut->setIconUri('http://example.com/icon.png');
        $sut->addUri('uri1');
        $sut->addUri('uri2');
        $sut->removeUri('uri2');
        $sut->addScope('scope1');
        $sut->addScope('scope2');
        $sut->addScope('scope3');
        $sut->removeScope('scope3');
        $sut->setType($expected['type']);
        $this->assertEquals($expected, $sut->jsonSerialize());
    }

    protected function createSutWithoutResource(
        bool            $expectSave = false,
        User|null|array $owner = null
    ): ResourceBuilder
    {
        $cache = $this->createMock(ResourceCache::class);
        $cache->expects($this->never())
            ->method('remove');

        return $sut = new ResourceBuilder(
            null,
            $this->createUserStorage($owner),
            'my-resource',
            $this->createApiClientMock($expectSave, function () use (&$sut) {
                return $sut;
            }),
            $cache
        );
    }

    protected function createSutWithResource(
        bool            $expectSave = false,
        User|null|array $owner = null
    ): ResourceBuilder
    {
        $ownerId = new Uuid('83335934-fc49-4c59-8199-de47c3d03ac3');
        if ($owner !== null) {
            if ($owner instanceof User) {
                $ownerId = $owner->getId();
            } else {
                $firstOption = $owner[0];
                $ownerId = end($firstOption)->getId();
            }
        }
        $userStorage = $this->createUserStorage($owner);
        $resource = new Resource(
            id: new Uuid('83335934-fc49-4c59-8199-de47c3d03ac5'),
            name: 'my-resource',
            displayName: 'displayName',
            ownerId: $ownerId,
            isUserManaged: $owner !== null,
            attributes: [],
            iconUri: null,
            uris: [],
            scopes: null,
            type: null,
            userStorage: $userStorage
        );
        $cache = $this->createMock(ResourceCache::class);
        if ($expectSave) {
            $cache->expects($this->once())
                ->method('remove')
                ->with(new Uuid('83335934-fc49-4c59-8199-de47c3d03ac5'));
        } else {
            $cache->expects($this->never())
                ->method('remove');
        }
        return $sut = new ResourceBuilder(
            $resource,
            $userStorage,
            null,
            $this->createApiClientMock($expectSave, function () use (&$sut) {
                return $sut;
            }),
            $cache
        );
    }

    protected function createApiClientMock(bool $expectSave, callable $getSavedInstance): KeycloakApiClient
    {
        $api = $this->createMock(KeycloakApiClient::class);
        if ($expectSave) {
            $api->expects($this->once())
                ->method('upsertResource')
                ->with($this->callback(fn($resource) => $getSavedInstance() === $resource));
        } else {
            $api->expects($this->never())
                ->method('upsertResource');
        }
        return $api;
    }

    protected function createUserStorage(User|null|array $owner = null): UserStorage
    {
        $userStorage = $this->createStub(UserStorage::class);
        if ($owner !== null) {
            if (is_array($owner)) {
                $userStorage->method('getOne')->willReturnMap($owner);
            } else {
                $userStorage->method('getOne')->willReturn($owner);
            }
        }
        return $userStorage;
    }
}
