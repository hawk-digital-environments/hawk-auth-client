<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Profiles;


use Hawk\AuthClient\Cache\CacheAdapterInterface;
use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Keycloak\Value\ConnectionConfig;
use Hawk\AuthClient\Profiles\ProfileStorage;
use Hawk\AuthClient\Profiles\Value\UserProfile;
use Hawk\AuthClient\Users\Value\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass(ProfileStorage::class)]
class ProfileStorageTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new ProfileStorage(
            $this->createStub(ConnectionConfig::class),
            $this->createStub(CacheAdapterInterface::class),
            $this->createStub(KeycloakApiClient::class)
        );
        $this->assertInstanceOf(ProfileStorage::class, $sut);
    }

    public function testItCanResolveTheProfileOfAUser(): void
    {
        $user = $this->createStub(User::class);
        $profile = $this->createStub(UserProfile::class);
        $cache = $this->createMock(CacheAdapterInterface::class);
        $cache->expects($this->once())->method('remember')->willReturn($profile);
        $sut = new ProfileStorage(
            $this->createStub(ConnectionConfig::class),
            $cache,
            $this->createStub(KeycloakApiClient::class)
        );

        $this->assertSame($profile, $sut->getProfileOfUser($user));
        // It should be cached now (counter test will fail if it's not)
        $this->assertSame($profile, $sut->getProfileOfUser($user));
    }

    public function testItCachesUserProfileCorrectly(): void
    {
        $config = $this->createStub(ConnectionConfig::class);
        $profileData = [
            UserProfile::ATTRIBUTE_USERNAME => 'foo',
            UserProfile::ATTRIBUTE_FIRST_NAME => 'bar',
            UserProfile::ATTRIBUTE_LAST_NAME => 'baz',
            UserProfile::ATTRIBUTE_EMAIL => 'qux@quox.de',
            'attributes' => [],
            'structure' => [],
            'additionalData' => []
        ];
        $user = $this->createStub(User::class);
        $user->method('getId')->willReturn('foo');
        $profile = $this->createStub(UserProfile::class);
        $profile->method('jsonSerialize')->willReturn($profileData);
        $api = $this->createMock(KeycloakApiClient::class);
        $api->expects($this->once())->method('fetchUserProfile')->with($user)->willReturn($profile);
        $cache = $this->createMock(CacheAdapterInterface::class);
        $cache->expects($this->once())->method('remember')
            ->willReturnCallback(function (
                string   $key,
                callable $valueGenerator,
                callable $valueToCache,
                callable $cacheToValue
            ) use ($profile, $profileData) {
                $this->assertEquals('keycloak.profile.foo', $key);
                $this->assertSame($profile, $valueGenerator());
                $this->assertEquals($profileData, $valueToCache($profile));
                $this->assertInstanceOf(UserProfile::class, $cacheToValue($profileData));
                return $profile;
            });

        $result = (new ProfileStorage($config, $cache, $api))->getProfileOfUser($user);
        $this->assertSame($profile, $result);
    }

    public function testItCanFlushResolvedInstances(): void
    {
        $user = $this->createStub(User::class);
        $profile = $this->createStub(UserProfile::class);
        $cache = $this->createMock(CacheAdapterInterface::class);
        $cache->expects($this->exactly(2))->method('remember')->willReturn($profile);
        $sut = new ProfileStorage(
            $this->createStub(ConnectionConfig::class),
            $cache,
            $this->createStub(KeycloakApiClient::class)
        );

        $this->assertSame($profile, $sut->getProfileOfUser($user));

        $sut->flushResolved();

        $this->assertSame($profile, $sut->getProfileOfUser($user));
    }

    public function testItCanUpdateTheProfile(): void
    {
        $data = ['foo' => 'bar'];
        $user = $this->createStub(User::class);
        $api = $this->createMock(KeycloakApiClient::class);
        $api->expects($this->once())->method('updateUserProfile')->with($user, $data);
        $sut = new ProfileStorage(
            $this->createStub(ConnectionConfig::class),
            $this->createStub(CacheAdapterInterface::class),
            $api
        );
        $sut->updateProfile($user, $data);
    }

}
