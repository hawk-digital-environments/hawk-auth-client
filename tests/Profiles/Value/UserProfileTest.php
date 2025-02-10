<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Profiles\Value;


use Hawk\AuthClient\Keycloak\Value\ConnectionConfig;
use Hawk\AuthClient\Profiles\Structure\ProfileStructure;
use Hawk\AuthClient\Profiles\Value\UserProfile;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UserProfile::class)]
class UserProfileTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new UserProfile(
            $this->createStub(ConnectionConfig::class),
            'test-username',
            'test-first-name',
            'test-last-name',
            'test-email',
            ['test-attribute' => 'test-value'],
            [],
            ['test-additional' => 'test-value']
        );
        $this->assertInstanceOf(UserProfile::class, $sut);
    }

    public function testItCanGetAllValuesAsExpected(): void
    {
        $sut = $this->createFullyConfiguredSut(
            $clientId,
            $attributes,
            $additionalData
        );

        // Root level attributes
        $this->assertEquals('test-username', $sut->getUsername());
        $this->assertEquals('test-username', $sut->getAttribute(UserProfile::ATTRIBUTE_USERNAME, clientId: false));
        $this->assertEquals('test-first-name', $sut->getFirstName());
        $this->assertEquals('test-first-name', $sut->getAttribute(UserProfile::ATTRIBUTE_FIRST_NAME, clientId: false));
        $this->assertEquals('test-last-name', $sut->getLastName());
        $this->assertEquals('test-last-name', $sut->getAttribute(UserProfile::ATTRIBUTE_LAST_NAME, clientId: false));
        $this->assertEquals('test-email', $sut->getEmail());
        $this->assertEquals('test-email', $sut->getAttribute(UserProfile::ATTRIBUTE_EMAIL, clientId: false));
        $this->assertFalse($sut->getAttribute(UserProfile::ATTRIBUTE_EMAIL_VERIFIED, clientId: false));

        // Get custom attributes
        $this->assertEquals('global value', $sut->getAttribute('test-attribute', clientId: false));
        $this->assertEquals('local value', $sut->getAttribute('test-attribute'));
        $this->assertEquals('local value', $sut->getAttribute('test-attribute', clientId: $clientId));
        $this->assertEquals(['foreign value 1', 'foreign value 2'], $sut->getAttribute('test-attribute', clientId: 'otherClient'));
        $this->assertNull($sut->getAttribute('test-attribute', clientId: 'unknownClient'));
        $this->assertTrue($sut->getAttribute('foo-attribute', true));

        // Other getters
        $this->assertSame($attributes, $sut->getRawAttributes());
        $this->assertSame($additionalData, $sut->getAdditionalData());
        $this->assertInstanceOf(ProfileStructure::class, $sut->getStructure());
    }

    public function testItCanBeIterated(): void
    {
        $sut = $this->createFullyConfiguredSut(
            $clientId,
            $attributs,
            $additionalData
        );

        // Global iterator
        $expected = [
            UserProfile::ATTRIBUTE_USERNAME => 'test-username',
            UserProfile::ATTRIBUTE_FIRST_NAME => 'test-first-name',
            UserProfile::ATTRIBUTE_LAST_NAME => 'test-last-name',
            UserProfile::ATTRIBUTE_EMAIL => 'test-email',
            UserProfile::ATTRIBUTE_EMAIL_VERIFIED => false,
            'test-attribute' => 'global value',
        ];
        $this->assertSame($expected, iterator_to_array($sut->getIterator(false), true), 'Global iterator');

        // Default iterator -> client only
        $expected = [
            'test-attribute' => 'local value',
        ];
        $this->assertSame($expected, iterator_to_array($sut, true), 'Default iterator -> client only');

        // Other client iterator
        $expected = [
            'test-attribute' => ['foreign value 1', 'foreign value 2'],
        ];
        $this->assertSame($expected, iterator_to_array($sut->getIterator('otherClient'), true), 'Other client iterator');
    }

    public function testItCanBeJsonSerializedAndRehydrated(): void
    {
        $sut = $this->createFullyConfiguredSut(
            attributes: $attributes,
            additionalData: $additionalData,
            structure: $structure,
            config: $config
        );

        $expected = [
            'username' => 'test-username',
            'firstName' => 'test-first-name',
            'lastName' => 'test-last-name',
            'email' => 'test-email',
            'attributes' => $attributes,
            'structure' => $structure,
            'additionalData' => $additionalData,
        ];

        $this->assertSame($expected, $sut->jsonSerialize());

        /** @noinspection PhpParamsInspection */
        $sut2 = UserProfile::fromArray($config, $sut->jsonSerialize());
        $this->assertEquals($sut, $sut2);
    }

    protected function createFullyConfiguredSut(&$clientId = null, &$attributes = null, &$additionalData = null, &$structure = null, &$config = null): UserProfile
    {
        $clientId = 'test-client-id';
        $config = $this->createStub(ConnectionConfig::class);
        $config->method('getClientId')->willReturn($clientId);

        $attributes = [
            'test-attribute' => ['global value'],
            'hawk.' . $clientId . '.test-attribute' => ['local value'],
            'hawk.otherClient.test-attribute' => [
                'foreign value 1',
                'foreign value 2',
            ],
        ];
        $structure = [
            'attributes' => [
                [
                    'name' => 'hawk.' . $clientId . '.test-attribute',
                    'multivalued' => false
                ],
                [
                    'name' => 'test-attribute',
                    'multivalued' => false
                ],
                [
                    'name' => 'hawk.otherClient.test-attribute',
                    'multivalued' => true
                ]
            ]
        ];
        $additionalData = [
            'test-additional' => 'test-value',
        ];
        return new UserProfile(
            $config,
            'test-username',
            'test-first-name',
            'test-last-name',
            'test-email',
            $attributes,
            $structure,
            $additionalData
        );
    }
}
