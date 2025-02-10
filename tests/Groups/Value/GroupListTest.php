<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Groups\Value;


use Hawk\AuthClient\Groups\Value\Group;
use Hawk\AuthClient\Groups\Value\GroupList;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GroupList::class)]
class GroupListTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new GroupList();
        $this->assertInstanceOf(GroupList::class, $sut);
    }

    public function testItCanIterate(): void
    {
        $group1 = $this->createStub(Group::class);
        $group2 = $this->createStub(Group::class);
        $group3 = $this->createStub(Group::class);
        $group31 = $this->createStub(Group::class);
        $group3->method('getChildren')->willReturn(new GroupList($group31));

        $sut = new GroupList($group1, $group2, $group3);

        $this->assertSame(
            [$group1, $group2, $group3],
            iterator_to_array($sut, false)
        );
    }

    public function testItCanIterateRecursively(): void
    {
        $group1 = $this->createStub(Group::class);
        $group11 = $this->createStub(Group::class);
        $group111 = $this->createStub(Group::class);
        $group112 = $this->createStub(Group::class);
        $group11->method('getChildren')->willReturn(new GroupList($group111, $group112));
        $group12 = $this->createStub(Group::class);
        $group1->method('getChildren')->willReturn(new GroupList($group11, $group12));
        $group2 = $this->createStub(Group::class);
        $group3 = $this->createStub(Group::class);
        $group31 = $this->createStub(Group::class);
        $group3->method('getChildren')->willReturn(new GroupList($group31));

        $sut = new GroupList($group1, $group2, $group3);

        $this->assertSame(
            [$group1, $group11, $group111, $group112, $group12, $group2, $group3, $group31],
            iterator_to_array($sut->getRecursiveIterator(), false)
        );
    }

    public function testItCanBeCreatedFromScalars(): void
    {
        $data = [
            [
                'id' => 'f47ac10b-58cc-4372-a567-0e02b2c3d001',
                'name' => 'name1',
                'path' => '/path1',
                'children' => []
            ],
            [
                'id' => 'f47ac10b-58cc-4372-a567-0e02b2c3d002',
                'name' => 'name2',
                'path' => '/path2',
                'children' => [
                    [
                        'id' => 'f47ac10b-58cc-4372-a567-0e02b2c3d021',
                        'name' => 'name21',
                        'path' => '/path21',
                        'children' => [
                            [
                                'id' => 'f47ac10b-58cc-4372-a567-0e02b2c3d221',
                                'name' => 'name211',
                                'path' => '/path211',
                                'children' => []
                            ]
                        ]
                    ],
                    [
                        'id' => 'f47ac10b-58cc-4372-a567-0e02b2c3d022',
                        'name' => 'name22',
                        'path' => '/path22',
                        'children' => []
                    ]
                ]
            ]
        ];

        $sut = GroupList::fromScalarList(...$data);

        $expected211 = new Group('f47ac10b-58cc-4372-a567-0e02b2c3d221', 'name211', '/path211', new GroupList());
        $expected21 = new Group('f47ac10b-58cc-4372-a567-0e02b2c3d021', 'name21', '/path21', new GroupList($expected211));
        $expected22 = new Group('f47ac10b-58cc-4372-a567-0e02b2c3d022', 'name22', '/path22', new GroupList());
        $expected2 = new Group('f47ac10b-58cc-4372-a567-0e02b2c3d002', 'name2', '/path2', new GroupList($expected21, $expected22));
        $expected1 = new Group('f47ac10b-58cc-4372-a567-0e02b2c3d001', 'name1', '/path1', new GroupList());

        $this->assertEquals(
            [$expected1, $expected2, $expected21, $expected211, $expected22],
            iterator_to_array($sut->getRecursiveIterator(), false)
        );
    }
}
