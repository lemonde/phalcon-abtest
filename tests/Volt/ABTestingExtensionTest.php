<?php

namespace ABTesting\Tests\Volt;

use ABTesting\Engine;
use ABTesting\Test\Test;
use ABTesting\Test\Variant;
use ABTesting\Tests\TestCase;
use ABTesting\Volt\ABTestingExtension;
use Phalcon\Di;
use Phalcon\Events\Manager as EventsManager;
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
        $this->assertEquals(
            ABTestingExtension::class . '::getTestHref(\'testName\', \'https://www.example.org\')',
            $ext->compileFunction('ab_test_href', $exportParams(['testName', 'https://www.example.org']))
        );
        $this->assertNull(
            $ext->compileFunction('not_mapped_function', $exportParams(['testName', 'https://www.example.org']))
        );
    }


    public function testGetTestResult()
    {
        $engine = $this->createMockForSingleton(Engine::class);
        $engine->expects($this->once())->method('isActivated')->willReturn(true);
        $engine
            ->expects($this->once())
            ->method('getTest')
            ->with('testName')
            ->willReturn(new Test('testName', [], new Variant('default', 'Default')));

        $this->assertEquals('Default', ABTestingExtension::getTestResult('testName'));
    }


    public function testGetDeactivatedTestResult()
    {
        $engine = $this->createMockForSingleton(Engine::class);
        $engine->expects($this->once())->method('isActivated')->willReturn(false);
        $engine
            ->expects($this->once())
            ->method('getTest')
            ->with('testName')
            ->willReturn(new Test('testName', [], new Variant('default', 'Default')));

        $this->assertEquals('Default', ABTestingExtension::getTestResult('testName'));
    }


    public function testGetUndefinedTestResult()
    {
        $engine = $this->createMockForSingleton(Engine::class);
        $eventsManager = $this->createMock(EventsManager::class);
        $engine->expects($this->any())->method('getEventsManager')->willReturn($eventsManager);
        $engine->expects($this->never())->method('isActivated');
        $engine
            ->expects($this->once())
            ->method('getTest')
            ->with('testName')
            ->willThrowException(new \Exception('Any exception thrown'));

        $this->assertEquals(null, ABTestingExtension::getTestResult('testName'));
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

        $test = $this->createMock(Test::class);
        $winner = $this->createMock(Variant::class);
        $winner->expects($this->any())->method('getIdentifier')->willReturn('default');
        $test->expects($this->any())->method('getIdentifier')->willReturn('testName');
        $test->expects($this->any())->method('getWinner')->willReturn($winner);
        $test->expects($this->any())->method('hasBattled')->willReturn(true);

        $engine = $this->createMockForSingleton(Engine::class);
        $engine->expects($this->once())->method('isActivated')->willReturn(true);

        $engine
            ->expects($this->once())
            ->method('getTest')
            ->with('testName')
            ->willReturn($test);
        $engine
            ->expects($this->once())
            ->method('savePrint')
            ->with('testName', 'default');

        $this->assertEquals('/the/ab_test_redirect/url', ABTestingExtension::getTestClick('testName', 'https://www.example.org'));
    }

    public function testGetForcedTestClick()
    {
        $url = $this->createMock(Url::class);
        $url
            ->expects($this->once())
            ->method('get')
            ->with(
                ['for' => 'ab_test_redirect', 'testName' => 'testName', 'winner' => 'forced'],
                ['u' => 'https://www.example.org']
            )
            ->willReturn('/the/ab_test_redirect/url_forced');

        $di = $this->createMockForSingleton(Di::class, '_default');
        $di
            ->expects($this->once())
            ->method('get')
            ->with('url')
            ->willReturn($url);

        $test = $this->createMock(Test::class);
        $winner = $this->createMock(Variant::class);
        $winner->expects($this->any())->method('getIdentifier')->willReturn('default');
        $forced = $this->createMock(Variant::class);
        $forced->expects($this->any())->method('getIdentifier')->willReturn('forced');
        $test->expects($this->any())->method('getIdentifier')->willReturn('testName');
        $test->expects($this->once())->method('getVariant')->with('forced')->willReturn($forced);
        $test->expects($this->any())->method('getWinner')->willReturn($winner);
        $test->expects($this->any())->method('hasBattled')->willReturn(true);

        $engine = $this->createMockForSingleton(Engine::class);
        $engine->expects($this->once())->method('isActivated')->willReturn(true);

        $engine
            ->expects($this->once())
            ->method('getTest')
            ->with('testName')
            ->willReturn($test);
        $engine
            ->expects($this->once())
            ->method('savePrint')
            ->with('testName', 'forced');

        $this->assertEquals('/the/ab_test_redirect/url_forced', ABTestingExtension::getTestClick('testName', 'https://www.example.org', 'forced'));
    }

    public function testGetDeactivatedTestClick()
    {
        $url = $this->createMock(Url::class);
        $url
            ->expects($this->never())
            ->method('get')
            ->with(
                ['for' => 'ab_test_redirect', 'testName' => 'testName', 'winner' => 'default'],
                ['u' => 'https://www.example.org']
            )
            ->willReturn('/the/ab_test_redirect/url');

        $di = $this->createMockForSingleton(Di::class, '_default');
        $di
            ->expects($this->never())
            ->method('get')
            ->with('url')
            ->willReturn($url);

        $test = $this->createMock(Test::class);
        $winner = $this->createMock(Variant::class);
        $winner->expects($this->any())->method('getIdentifier')->willReturn('default');
        $test->expects($this->any())->method('getIdentifier')->willReturn('testName');
        $test->expects($this->any())->method('getWinner')->willReturn($winner);
        $test->expects($this->any())->method('hasBattled')->willReturn(true);

        $engine = $this->createMockForSingleton(Engine::class);
        $engine->expects($this->once())->method('isActivated')->willReturn(false);

        $engine
            ->expects($this->never())
            ->method('getTest')
            ->with('testName')
            ->willReturn($test);
        $engine
            ->expects($this->never())
            ->method('savePrint')
            ->with('testName', 'default');

        $this->assertEquals('https://www.example.org', ABTestingExtension::getTestClick('testName', 'https://www.example.org'));
    }

    public function testGetTestDefaultClick()
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

        $test = $this->createMock(Test::class);
        $winner = $this->createMock(Variant::class);
        $winner->expects($this->any())->method('getIdentifier')->willReturn('default');
        $winner->expects($this->any())->method('isDefault')->willReturn(true);
        $test->expects($this->any())->method('getIdentifier')->willReturn('testName');
        $test->expects($this->any())->method('getWinner')->willReturn($winner);
        $test->expects($this->any())->method('hasBattled')->willReturn(true);

        $engine = $this->createMockForSingleton(Engine::class);
        $engine->expects($this->once())->method('isActivated')->willReturn(true);
        $engine
            ->expects($this->once())
            ->method('getTest')
            ->with('testName')
            ->willReturn($test);
        $engine
            ->expects($this->once())
            ->method('savePrint')
            ->with('testName', 'default');

        $this->assertEquals('https://www.example.org', ABTestingExtension::getTestClick('testName', 'https://www.example.org'));
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
        $eventsManager = $this->createMock(EventsManager::class);
        $engine->expects($this->any())->method('getEventsManager')->willReturn($eventsManager);
        $engine->expects($this->once())->method('isActivated')->willReturn(true);
        $engine
            ->expects($this->once())
            ->method('getTest')
            ->with('testName')
            ->willThrowException(new \Exception('Any exception thrown'));
        $engine
            ->expects($this->never())
            ->method('savePrint');

        $this->assertEquals('https://www.example.org', ABTestingExtension::getTestClick('testName', 'https://www.example.org'));
    }

    public function testGetTestHref()
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

        $test = $this->createMock(Test::class);
        $winner = $this->createMock(Variant::class);
        $winner->expects($this->any())->method('getIdentifier')->willReturn('default');
        $test->expects($this->any())->method('getIdentifier')->willReturn('testName');
        $test->expects($this->any())->method('getWinner')->willReturn($winner);
        $test->expects($this->any())->method('hasBattled')->willReturn(true);

        $engine = $this->createMockForSingleton(Engine::class);
        $engine->expects($this->once())->method('isActivated')->willReturn(true);

        $engine
            ->expects($this->once())
            ->method('getTest')
            ->with('testName')
            ->willReturn($test);
        $engine
            ->expects($this->once())
            ->method('savePrint')
            ->with('testName', 'default');
        $this->assertEquals('href="https://www.example.org"  onmousedown="this.href = \'/the/ab_test_redirect/url\'" ', ABTestingExtension::getTestHref('testName', 'https://www.example.org'));
    }
}
