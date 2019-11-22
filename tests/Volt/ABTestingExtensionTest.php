<?php

namespace ABTesting\Tests\Volt;

use ABTesting\Engine;
use ABTesting\Test\Test;
use ABTesting\Test\Variant;
use ABTesting\Tests\TestCase;
use ABTesting\Volt\ABTestingExtension;
use Phalcon\Di;
use Phalcon\Mvc\Url;

class ABTestingExtensionTest extends TestCase
{
    public function testCompileFunction()
    {
        $exportParams = function(array $params) {
          return join(', ', array_map(function ($val) {
              return var_export($val, true);
          }, $params));
        };
        $ext = new ABTestingExtension();
        $this->assertEquals(
            ABTestingExtension::class . "::getTestResult('testName')",
            $ext->compileFunction('ab_test_result', $exportParams(['testName']))
        );
        $this->assertEquals(
            ABTestingExtension::class . '::getTestClick(\'testName\', \'https://www.example.org\')',
            $ext->compileFunction('ab_test_click', $exportParams(['testName', 'https://www.example.org']))
        );
        $this->assertNull(
            $ext->compileFunction('not_mapped_function', $exportParams(['testName', 'https://www.example.org']))
        );
    }


    public function testGetTestResult()
    {
        $engine = $this->createMockForSingleton(Engine::class);
        $engine
            ->expects($this->once())
            ->method('getTest')
            ->with('testName')
            ->willReturn(new Test('testName', [], new Variant('default', 'Default')));

        $this->assertEquals(var_export('Default', true), ABTestingExtension::getTestResult('testName'));
    }


    public function testGetUndefinedTestResult()
    {
        $engine = $this->createMockForSingleton(Engine::class);
        $engine
            ->expects($this->once())
            ->method('getTest')
            ->with('testName')
            ->willThrowException(new \Exception('Any exception thrown'));

        $this->assertEquals(var_export(null, true), ABTestingExtension::getTestResult('testName'));
    }

    public function testGetTestClick()
    {
        $url = $this->createMock(Url::class);
        $url
            ->expects($this->once())
            ->method('get')
            ->with(
                ['for' => 'ab_test_redirect', 'testName' => 'testName', 'winner' => 'default'],
                ['u' => 'https://www.example.org']
            )
            ->willReturn('/the/ab_test_redirect/url');

        $di = $this->createMockForSingleton(Di::class, '_default');
        $di
            ->expects($this->once())
            ->method('get')
            ->with('url')
            ->willReturn($url);

        $engine = $this->createMockForSingleton(Engine::class);
        $engine
            ->expects($this->once())
            ->method('getTest')
            ->with('testName')
            ->willReturn(new Test('testName', [], new Variant('default', 'Default')));
        $engine
            ->expects($this->once())
            ->method('savePrint')
            ->with('testName', 'default');

        $this->assertEquals(var_export('/the/ab_test_redirect/url', true), ABTestingExtension::getTestClick('testName', 'https://www.example.org'));
    }

    public function testGetUndefinedTestClick()
    {
        $url = $this->createMock(Url::class);
        $url
            ->expects($this->never())
            ->method('get');

        $di = $this->createMockForSingleton(Di::class, '_default');
        $di
            ->expects($this->never())
            ->method('get');

        $engine = $this->createMockForSingleton(Engine::class);
        $engine
            ->expects($this->once())
            ->method('getTest')
            ->with('testName')
            ->willThrowException(new \Exception('Any exception thrown'));
        $engine
            ->expects($this->never())
            ->method('savePrint');

        $this->assertEquals(var_export(null, true), ABTestingExtension::getTestClick('testName', 'https://www.example.org'));
    }
}
