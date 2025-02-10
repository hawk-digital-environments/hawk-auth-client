<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Resources;


use Hawk\AuthClient\Cache\CacheAdapterInterface;
use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Resources\ResourceCache;
use Hawk\AuthClient\Resources\ResourceFactory;
use Hawk\AuthClient\Resources\Value\Resource;
use Hawk\AuthClient\Resources\Value\ResourceConstraints;
use Hawk\AuthClient\Tests\TestUtils\PartialMockWithConstructorArgsTrait;
use Hawk\AuthClient\Tests\TestUtils\TestCacheAdapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ResourceCache::class)]
class ResourceCacheTest extends TestCase
{
    use PartialMockWithConstructorArgsTrait;

    public function testItConstructs(): void
    {
        $sut = new ResourceCache(
            $this->createStub(CacheAdapterInterface::class),
            $this->createStub(ResourceFactory::class),
            $this->createStub(KeycloakApiClient::class)
        );
        $this->assertInstanceOf(ResourceCache::class, $sut);
    }

    public function testItCanGetAResourceIdFromUuid(): void
    {
        $cache = $this->createMock(CacheAdapterInterface::class);
        $cache->expects($this->never())->method('remember');
        $resourceFactory = $this->createStub(ResourceFactory::class);
        $api = $this->createStub(KeycloakApiClient::class);
        $sut = new ResourceCache($cache, $resourceFactory, $api);
        $this->assertEquals('f47ac10b-58cc-4372-a567-0e02b2c3d001', $sut->getResourceId('f47ac10b-58cc-4372-a567-0e02b2c3d001'));
    }

    public function testItCanGetAResourceIdByNameAndReuseItForLaterCalls(): void
    {
        $cache = $this->createMock(CacheAdapterInterface::class);
        $cache->expects($this->once())->method('remember')->willReturn('f47ac10b-58cc-4372-a567-0e02b2c3d001');
        $resourceFactory = $this->createStub(ResourceFactory::class);
        $api = $this->createStub(KeycloakApiClient::class);
        $sut = new ResourceCache($cache, $resourceFactory, $api);

        $this->assertEquals('f47ac10b-58cc-4372-a567-0e02b2c3d001', $sut->getResourceId('foo'));
        $this->assertEquals('f47ac10b-58cc-4372-a567-0e02b2c3d001', $sut->getResourceId('foo'));
        $this->assertEquals('f47ac10b-58cc-4372-a567-0e02b2c3d001', $sut->getResourceId('foo'));
    }

    public function testItCachesTheResourceIdByNameMapCorrectlyWhenRetrievingItDirectly(): void
    {
        $resource = $this->createStub(Resource::class);
        $resource->method('getId')->willReturn('f47ac10b-58cc-4372-a567-0e02b2c3d001');
        $cache = $this->createMock(CacheAdapterInterface::class);
        $cache->expects($this->once())
            ->method('remember')
            ->willReturnCallback(function (
                string   $key,
                callable $valueGenerator,
                callable $valueToCache,
                callable $cacheToValue,
                callable $ttl
            ) use ($resource) {
                $this->assertEquals(ResourceCache::CACHE_KEY . '.nameToIdMap.' . hash('sha256', 'foo'), $key);
                $this->assertEquals($resource, $valueGenerator());
                $this->assertEquals(60 * 60, $ttl(null));
                $this->assertEquals(null, $ttl($resource));

                $this->assertFalse($valueToCache(null));
                $this->assertEquals('f47ac10b-58cc-4372-a567-0e02b2c3d001', $valueToCache($resource));
                $this->assertNull($cacheToValue(false));
                $this->assertNull($cacheToValue(null));
                $this->assertEquals('foo', $cacheToValue('foo'));
                return 'f47ac10b-58cc-4372-a567-0e02b2c3d001';
            });
        $api = $this->createMock(KeycloakApiClient::class);
        $api->expects($this->once())
            ->method('fetchResourceByName')->with('foo')
            ->willReturn($resource);
        $sut = new ResourceCache($cache, $this->createStub(ResourceFactory::class), $api);
        $this->assertEquals('f47ac10b-58cc-4372-a567-0e02b2c3d001', $sut->getResourceId('foo'));
    }

    public function testItAutomaticallyCachesTheResourceIdByNameRecordAutomaticallyWhenCachingAResource(): void
    {
        $resource = $this->createStub(Resource::class);
        $resource->method('getId')->willReturn('f47ac10b-58cc-4372-a567-0e02b2c3d001');
        $resource->method('getName')->willReturn('name');

        $cache = new TestCacheAdapter();
        $expectedCacheKey = ResourceCache::CACHE_KEY . '.nameToIdMap.' . hash('sha256', $resource->getName());

        $api = $this->createMock(KeycloakApiClient::class);
        $api->expects($this->once())
            ->method('fetchResourcesByIds')
            ->with($resource->getId())
            ->willReturn([$resource]);

        $sut = new ResourceCache($cache, $this->createStub(ResourceFactory::class), $api);

        $this->assertSame($resource, $sut->getOne($resource->getId()));
        $this->assertEquals($resource->getId(), $sut->getResourceId($resource->getName()));
        $this->assertEquals($resource->getId(), $cache->get($expectedCacheKey));
    }

    public function testItResetsTheLoadedNameToIdMapWhenResolvedObjectsAreFlushed(): void
    {
        $cache = $this->createMock(CacheAdapterInterface::class);
        $cache->expects($this->exactly(2))->method('remember')->willReturn('f47ac10b-58cc-4372-a567-0e02b2c3d001');
        $resourceFactory = $this->createStub(ResourceFactory::class);
        $api = $this->createStub(KeycloakApiClient::class);
        $sut = new ResourceCache($cache, $resourceFactory, $api);

        $this->assertEquals('f47ac10b-58cc-4372-a567-0e02b2c3d001', $sut->getResourceId('foo'));

        $sut->flushResolved();
        $this->assertEquals('f47ac10b-58cc-4372-a567-0e02b2c3d001', $sut->getResourceId('foo'));
        $this->assertEquals('f47ac10b-58cc-4372-a567-0e02b2c3d001', $sut->getResourceId('foo'));
    }

    public function testItCanGetResourceIdStream(): void
    {
        $constraints = $this->createStub(ResourceConstraints::class);
        $cache = $this->createStub(CacheAdapterInterface::class);
        $api = $this->createMock(KeycloakApiClient::class);
        $api->expects($this->once())
            ->method('fetchResourceIdStream')
            ->with($constraints, $cache)
            ->willReturn(['foo', 'bar', 'baz']);
        $sut = new ResourceCache($cache, $this->createStub(ResourceFactory::class), $api);
        $this->assertEquals(['foo', 'bar', 'baz'], iterator_to_array($sut->getResourceIdStream($constraints), false));
    }

    public function testItWorksAsEntityCache(): void
    {
        $resourceToCache = $this->createStub(Resource::class);
        $resourceToCache->method('jsonSerialize')->willReturn(['id' => 'foo']);
        $resourceFromCache = $this->createStub(Resource::class);
        $resourceFactory = $this->createMock(ResourceFactory::class);
        $resourceFactory->expects($this->once())->method('makeResourceFromCacheData')->with(['id' => 'foo'])->willReturn($resourceFromCache);
        $api = $this->createMock(KeycloakApiClient::class);
        $api->expects($this->once())->method('fetchResourcesByIds')->with('foo')->willReturn([$resourceToCache]);
        $cache = new TestCacheAdapter();

        // First fetch -> get from api
        $fetchedUser = (new ResourceCache($cache, $resourceFactory, $api))->getOne('foo');
        $this->assertSame($resourceToCache, $fetchedUser);

        // Second fetch -> get from cache
        $fetchedUser = (new ResourceCache($cache, $resourceFactory, $api))->getOne('foo');
        $this->assertSame($resourceFromCache, $fetchedUser);
    }
}
