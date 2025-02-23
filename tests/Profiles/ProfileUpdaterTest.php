<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Profiles;


use Hawk\AuthClient\Keycloak\Value\ConnectionConfig;
use Hawk\AuthClient\Profiles\ProfileStorage;
use Hawk\AuthClient\Profiles\ProfileUpdater;
use Hawk\AuthClient\Profiles\Value\UserProfile;
use Hawk\AuthClient\Users\Value\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProfileUpdater::class)]
class ProfileUpdaterTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new ProfileUpdater(
            $this->createStub(User::class),
            $this->createStub(ConnectionConfig::class),
            $this->createStub(ProfileStorage::class),
            false
        );
        $this->assertInstanceOf(ProfileUpdater::class, $sut);
    }

    public function testItCreatesAChangeSetAndPersistsIt(): void
    {
        $expectedChangeSet = [
            UserProfile::ATTRIBUTE_USERNAME => 'test-username',
            UserProfile::ATTRIBUTE_EMAIL => 'test-email',
            UserProfile::ATTRIBUTE_EMAIL_VERIFIED => true,
            UserProfile::ATTRIBUTE_FIRST_NAME => 'test-first-name',
            UserProfile::ATTRIBUTE_LAST_NAME => 'test-last-name',
            'hawk.test-client-id.test-attribute' => ['test-value'],
            'hawk.test-client-id.test-attribute-2' => ['test-value-2'],
            'hawk.other-client.test-attribute-2' => ['test-value-3'],
            'test-attribute-2' => ['test-value-4'],
        ];

        $user = $this->createStub(User::class);

        $config = $this->createStub(ConnectionConfig::class);
        $config->method('getClientId')->willReturn('test-client-id');

        $profileStorage = $this->createMock(ProfileStorage::class);
        $profileStorage->expects($this->once())->method('updateProfile')->with($user, $expectedChangeSet, false);

        $sut = new ProfileUpdater($user, $config, $profileStorage, false);

        $sut->setUsername('test-username');
        $sut->setEmail('test-email', true);
        $sut->setFirstName('test-first-name');
        $sut->setLastName('test-last-name');
        $sut->set('test-attribute', 'test-value');
        $sut->set('test-attribute-2', 'test-value-2');
        $sut->set('test-attribute-2', 'test-value-3', 'other-client');
        $sut->set('test-attribute-2', 'test-value-4', false);
        $sut->save();
    }

    public function testItForwardsAdminState(): void
    {
        $user = $this->createStub(User::class);
        $profileStorage = $this->createMock(ProfileStorage::class);
        $profileStorage->expects($this->once())->method('updateProfile')->with($user, $this->isArray(), true);

        $sut = new ProfileUpdater(
            $this->createStub(User::class),
            $this->createStub(ConnectionConfig::class),
            $profileStorage,
            true
        );

        $sut->set('test-attribute', 'test-value');
        $sut->save();
    }

}
