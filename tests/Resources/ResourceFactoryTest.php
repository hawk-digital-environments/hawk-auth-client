<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Resources;


use Hawk\AuthClient\Resources\ResourceFactory;
use Hawk\AuthClient\Resources\Value\Resource;
use Hawk\AuthClient\Resources\Value\ResourceScopes;
use Hawk\AuthClient\Users\UserStorage;
use Hawk\AuthClient\Users\Value\UserContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ResourceFactory::class)]
class ResourceFactoryTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new ResourceFactory($this->createStub(UserContext::class));
        $this->assertInstanceOf(ResourceFactory::class, $sut);
    }

    public static function provideTestItCanCreateAResourceFromKeycloakData(): iterable
    {
        yield 'without scopes' => [
            <<<JSON
{
		"name": "Default Resource",
		"type": "urn:users:resources:default",
		"owner": {
			"id": "72c97a1f-1ef9-4ba4-8a9d-3cd844488591",
			"name": "users"
		},
		"ownerManagedAccess": false,
		"attributes": {},
		"_id": "fa05c79f-bc41-416a-ad85-2669d72461a5",
		"uris": [
			"/*"
		]
	}
JSON,
            [
                'id' => 'fa05c79f-bc41-416a-ad85-2669d72461a5',
                'name' => 'Default Resource',
                'displayName' => null,
                'owner' => '72c97a1f-1ef9-4ba4-8a9d-3cd844488591',
                'isUserManaged' => false,
                'attributes' => [],
                'iconUri' => '',
                'uris' => ['/*'],
                'scopes' => [],
                'type' => 'urn:users:resources:default'
            ]
        ];
        yield 'with scopes' => [
            <<<JSON
	{
		"name": "uma-resource-3",
		"owner": {
			"id": "0d0498a6-0573-4b8e-a0ae-4752ccb961e0",
			"name": "bar"
		},
		"ownerManagedAccess": true,
		"attributes": {},
		"_id": "fcee7863-b2df-4e33-8dc6-9c0f94834374",
		"uris": [],
		"scopes": [
			{
				"id": "17959fe5-dbe8-40d6-9cf1-29fd3f691358",
				"name": "post-updates"
			},
			{
				"id": "5b30d252-4ba1-44bb-9c0a-64455bc61d8f",
				"name": "read-public"
			}
		]
	}
JSON,
            [
                'id' => 'fcee7863-b2df-4e33-8dc6-9c0f94834374',
                'name' => 'uma-resource-3',
                'displayName' => null,
                'owner' => '0d0498a6-0573-4b8e-a0ae-4752ccb961e0',
                'isUserManaged' => true,
                'attributes' => [],
                'iconUri' => '',
                'uris' => [],
                'scopes' => ['post-updates', 'read-public'],
                'type' => null
            ]
        ];
    }

    #[DataProvider('provideTestItCanCreateAResourceFromKeycloakData')]
    public function testItCanCreateAResourceFromKeycloakData(string $jsonData, array $expected): void
    {
        $userStorage = $this->createStub(UserStorage::class);
        $context = $this->createStub(UserContext::class);
        $context->method('getStorage')->willReturn($userStorage);
        $result = (new ResourceFactory($context))->makeResourceFromKeycloakData(json_decode($jsonData, true));
        $this->assertEquals($expected, $result->jsonSerialize());
    }

    public function testItCanCreateAResourceFromCacheData(): void
    {
        $userStorage = $this->createStub(UserStorage::class);
        $context = $this->createStub(UserContext::class);
        $context->method('getStorage')->willReturn($userStorage);

        $resource = new Resource(
            'fa05c79f-bc41-416a-ad85-2669d72461a5',
            'name',
            'displayName',
            '72c97a1f-1ef9-4ba4-8a9d-3cd844488591',
            false,
            [],
            '',
            ['/*'],
            new ResourceScopes('read', 'write'),
            'my-type',
            $userStorage
        );

        $result = (new ResourceFactory($context))->makeResourceFromCacheData($resource->jsonSerialize());
        $this->assertNotSame($resource, $result);
        $this->assertEquals($resource, $result);
    }

}
