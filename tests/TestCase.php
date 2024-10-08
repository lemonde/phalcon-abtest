<?php

namespace ABTesting\Tests;

use ReflectionClass;

class TestCase extends \PHPUnit\Framework\TestCase
{
    private $mockedSingleton = [];

    protected function tearDown(): void
    {
        foreach ($this->mockedSingleton as $singleton) {
            $this->resetSingleton($singleton[0], $singleton[1]);
        }
    }

    public function createMockForSingleton(string $className, string $singletonProperty = 'instance', array $methods = null)
    {
        if (null !== $methods) {
            $mock = $this->createPartialMock($className, $methods);
        } else {
            $mock = $this->createMock($className);
        }
        $reflectionClass = new ReflectionClass($className);
        $reflectionProp = $reflectionClass->getProperty($singletonProperty);
        $reflectionProp->setAccessible(true);
        $reflectionProp->setValue(null, $mock);
        $this->mockedSingleton[] = [$className, $singletonProperty];
        return $mock;
    }
    public function resetSingleton(string $className, string $singletonProperty = 'instance')
    {
        $reflectionClass = new ReflectionClass($className);
        $reflectionProp = $reflectionClass->getProperty($singletonProperty);
        $reflectionProp->setAccessible(true);
        $reflectionProp->setValue(null, null);
    }
}
