<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\TestUtils;


use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

trait PartialMockWithConstructorArgsTrait
{
    /**
     * @template RealInstanceType of object
     *
     * @param class-string<RealInstanceType> $originalClassName
     * @param array $methods
     * @param array $constructorArgs
     * @return MockObject&RealInstanceType
     */
    protected function createPartialMockWithConstructorArgs(string $originalClassName, array $methods, array $constructorArgs): object
    {
        if (!$this instanceof TestCase) {
            throw new \RuntimeException('This trait can only be used in a PHPUnit test case');
        }

        return $this->getMockBuilder($originalClassName)
            ->setConstructorArgs($constructorArgs)
            ->enableOriginalConstructor()
            ->disableOriginalClone()
            ->onlyMethods($methods)
            ->getMock();
    }
}
