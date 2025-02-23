<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Resources\Value;


use Hawk\AuthClient\Exception\ResourceOwnerNotFoundException;
use Hawk\AuthClient\Resources\Value\Resource;
use Hawk\AuthClient\Resources\Value\ResourceScopes;
use Hawk\AuthClient\Tests\TestUtils\DummyUuid;
use Hawk\AuthClient\Users\UserStorage;
use Hawk\AuthClient\Users\Value\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Resource::class)]
#[CoversClass(ResourceOwnerNotFoundException::class)]
class ResourceTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new Resource(
            id: new DummyUuid(),
            name: 'name',
            displayName: 'displayName',
            ownerId: new DummyUuid(),
            isUserManaged: false,
            attributes: [],
            iconUri: null,
            uris: [],
            scopes: null,
            type: null,
            userStorage: $this->createStub(UserStorage::class)
        );
        $this->assertInstanceOf(Resource::class, $sut);
    }

    public static function providedTestItCanGetValuesCases(): iterable
    {
        yield 'id' => [
            fn(Resource $sut) => $sut->getId(),
            new DummyUuid(),
        ];
        yield 'name' => [
            fn(Resource $sut) => $sut->getName(),
            'name',
        ];
        yield 'displayName' => [
            fn(Resource $sut) => $sut->getDisplayName(),
            'displayName',
        ];
        yield 'displayName fallback' => [
            fn(Resource $sut) => $sut->getDisplayName(),
            'name',
            ['displayName' => null],
        ];
        yield 'owner' => (static function () {
            $owner = self::createStub(User::class);
            $userStorage = self::createStub(UserStorage::class);
            $userStorage->method('getOne')->willReturn($owner);
            return [
                fn(Resource $sut) => $sut->getOwner(),
                $owner,
                ['userStorage' => $userStorage],
            ];
        })();
        yield 'isUserManaged' => [
            fn(Resource $sut) => $sut->isUserManaged(),
            false,
        ];
        yield 'attributes' => [
            fn(Resource $sut) => $sut->getAttributes(),
            null,
        ];
        yield 'attributes with values' => [
            fn(Resource $sut) => $sut->getAttributes(),
            ['key' => 'value', 'other' => 'value2'],
            ['attributes' => ['key' => ['value'], 'other' => ['value2']]],
        ];
        yield 'single attribute' => [
            fn(Resource $sut) => $sut->getAttribute('key'),
            'value',
            ['attributes' => ['key' => ['value']]],
        ];
        yield 'not existing attribute' => [
            fn(Resource $sut) => $sut->getAttribute('key'),
            null,
            ['attributes' => ['other' => ['value']]],
        ];
        yield 'not existing attribute with default' => [
            fn(Resource $sut) => $sut->getAttribute('key', 'default'),
            'default',
            ['attributes' => ['other' => ['value']]],
        ];
        yield 'iconUri' => [
            fn(Resource $sut) => $sut->getIconUri(),
            null,
        ];
        yield 'iconUri with emtpy string' => [
            fn(Resource $sut) => $sut->getIconUri(),
            null,
            ['iconUri' => ''],
        ];
        yield 'iconUri with value' => [
            fn(Resource $sut) => $sut->getIconUri(),
            'uri',
            ['iconUri' => 'uri'],
        ];
        yield 'uris' => [
            fn(Resource $sut) => $sut->getUris(),
            null,
        ];
        yield 'uris with values' => [
            fn(Resource $sut) => $sut->getUris(),
            ['uri1', 'uri2'],
            ['uris' => ['uri1', 'uri2']],
        ];
        yield 'scopes' => [
            fn(Resource $sut) => $sut->getScopes(),
            null,
        ];
        yield 'with empty scopes' => (function () {
            $scopes = new ResourceScopes();
            return [
                fn(Resource $sut) => $sut->getScopes(),
                $scopes,
                ['scopes' => $scopes],
            ];
        })();
        yield 'scopes can be accessed' => [
            fn(Resource $sut) => $sut->getScopes()?->hasAny('bar'),
            true,
            ['scopes' => new ResourceScopes('foo', 'bar')],
        ];
        yield 'type' => [
            fn(Resource $sut) => $sut->getType(),
            null,
        ];
        yield 'type with value' => [
            fn(Resource $sut) => $sut->getType(),
            'type',
            ['type' => 'type'],
        ];
        yield 'type with empty string' => [
            fn(Resource $sut) => $sut->getType(),
            null,
            ['type' => ''],
        ];
    }

    #[DataProvider('providedTestItCanGetValuesCases')]
    public function testItCanGetValues(
        callable $extractor,
        mixed    $expected,
        array    $argOverrides = []
    ): void
    {
        $constructorArgs = array_merge(
            [
                'id' => new DummyUuid(),
                'name' => 'name',
                'displayName' => 'displayName',
                'ownerId' => new DummyUuid(1),
                'isUserManaged' => false,
                'attributes' => [],
                'iconUri' => null,
                'uris' => [],
                'scopes' => null,
                'type' => null,
                'userStorage' => $this->createStub(UserStorage::class),
            ],
            $argOverrides
        );

        $sut = new Resource(...array_values($constructorArgs));

        $value = $extractor($sut);
        $this->assertEquals($expected, $value);
    }

    public function testItThrowsIfTheResourceOwnerDoesNotExist(): void
    {
        $this->expectException(ResourceOwnerNotFoundException::class);
        $sut = new Resource(
            id: new DummyUuid(),
            name: 'name',
            displayName: 'displayName',
            ownerId: new DummyUuid(),
            isUserManaged: false,
            attributes: [],
            iconUri: null,
            uris: [],
            scopes: null,
            type: null,
            userStorage: $this->createStub(UserStorage::class)
        );
        $sut->getOwner();
    }

    public function testItCanBeJsonEncoded(): void
    {
        $id = new DummyUuid();
        $ownerId = new DummyUuid(2);
        $sut = new Resource(
            id: $id,
            name: 'name',
            displayName: 'displayName',
            ownerId: $ownerId,
            isUserManaged: false,
            attributes: [],
            iconUri: null,
            uris: [],
            scopes: null,
            type: null,
            userStorage: $this->createStub(UserStorage::class)
        );

        $json = json_encode($sut);
        $this->assertIsString($json);
        $this->assertJsonStringEqualsJsonString(
            <<<JSON
{
  "id": "$id",
  "name": "name",
  "displayName": "displayName",
  "owner": "$ownerId",
  "isUserManaged": false,
  "attributes": [],
  "iconUri": null,
  "uris": [],
  "scopes": [],
  "type": null
}
JSON,
            $json
        );

    }
}
