<?php

namespace ABTesting\Tests;

use ABTesting\Chooser\PercentChooser;
use ABTesting\Counter\AbTestCounter;
use ABTesting\Engine;
use ABTesting\Test\Test;
use Phalcon\Config;
use Phalcon\DiInterface;
use Phalcon\Events\Manager as EventsManager;
use Phalcon\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class EngineTest extends TestCase
{
    public function setUp()
    {
        unset($_SERVER['HTTP_USER_AGENT']);
        $reflection = new ReflectionClass(Engine::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true); // now we can modify that :)
        $instance->setValue(null, null); // instance is gone
        $instance->setAccessible(false); // clean up
    }

    /**
     * @dataProvider getUserAgent
     * @param string $userAgent
     * @throws Exception
     */
    public function testGetInstance(string $userAgent) {
        $_SERVER['HTTP_USER_AGENT'] = $userAgent;

        $eventManager = $this->createMock(EventsManager::class);

        $tests = [
            'phpunit_ab_test' => [
                'variants' => [
                    'test_A' => 'test A',
                    'test_B' => 'test B',
                ],
                'chooser' => [PercentChooser::class],
                'default' => 'test_A'
            ],
            'phpunit_ab_test_bis' => [
                'variants' => [
                    'test_A' => 'test A',
                    'test_B' => 'test B',
                ],
                'chooser' => PercentChooser::class,
                'default' => 'default'
            ],
        ];

        $di = $this->getDi($tests);

        $engine = Engine::getInstance($di);
        $engine->setEventsManager($eventManager);
        $this->assertCount(2, $engine->getTests());
        $this->assertEquals($eventManager, $engine->getEventsManager());
        $this->assertContainsOnlyInstancesOf(Test::class, $engine->getTests());
        $this->assertInstanceOf(Test::class, $engine->getTest('phpunit_ab_test'));
        $this->assertInstanceOf(Test::class, $engine->getTest('phpunit_ab_test_bis'));
        $this->assertInstanceOf(AbTestCounter::class, $engine->getCounter());

        return $engine;
    }

    /**
     * @throws Exception
     * @expectedException \ABTesting\Exception\AbTestingException
     * @expectedExceptionMessage  Unconfigured AB test with name phpunit_ab_test
     */
    public function testGetEmptyInstance() {
        $di = $this->getDi([]);
        $engine = Engine::getInstance($di);
        $this->assertCount(0, $engine->getTests());
        $this->assertContainsOnlyInstancesOf(Test::class, $engine->getTests());
        $engine->getTest('phpunit_ab_test');
    }

    public function testActivated(){
        $di = $this->getDi([]);
        $engine = Engine::getInstance($di);
        $this->assertTrue($engine->isActivated());
        $engine->activate();
        $this->assertTrue($engine->isActivated());
        $engine->deactivate();
        $this->assertFalse($engine->isActivated());
        $engine->activate();
        $this->assertTrue($engine->isActivated());
    }

    public function testSavePrint(){
        list($engine, $counter) = $this->getEngine([]);

        $eventsManager = $this->createMock(EventsManager::class);
        $eventsManager
            ->expects($this->once())
            ->method('fire')
            ->with('abtest:beforePrint', $this->isInstanceOf(Engine::class), ['sample_test', 'sample_template']);

        $counter
            ->expects($this->once())
            ->method('saveCounter')
            ->with('print', 'desktop', 'sample_test', 'sample_template');

        $engine->setEventsManager($eventsManager);
        $engine->savePrint('sample_test', 'sample_template');
    }

    public function testSaveClick(){
        list($engine, $counter) = $this->getEngine([]);

        $eventsManager = $this->createMock(EventsManager::class);
        $eventsManager
            ->expects($this->once())
            ->method('fire')
            ->with('abtest:beforeClick', $this->isInstanceOf(Engine::class), ['sample_test', 'sample_template']);

        $counter
            ->expects($this->once())
            ->method('saveCounter')
            ->with('click', 'desktop', 'sample_test', 'sample_template');

        $engine->setEventsManager($eventsManager);
        $engine->saveClick('sample_test', 'sample_template');
    }

    public function getUserAgent()
    {
        return [
            'Desktop' => ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.169 Safari/537.36'],
            'Tablet' => ['Mozilla/5.0 (iPad; CPU OS 12_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148'],
            'Mobile' => ['"Mozilla/5.0 (iPhone; CPU iPhone OS 8_3 like Mac OS X) AppleWebKit/600.1.4 (KHTML, like Gecko) Version/8.0 Mobile/12F70 Safari/600.1.4"'],
        ];
    }

    /**
     * @param array $tests
     * @return array
     * @throws \ReflectionException
     */
    public function getEngine(array $tests = [])
    {
        $counter = $this->createMock(AbTestCounter::class);
        $di = $this->getDi($tests);
        $engine = Engine::getInstance($di);

        $reflection = new ReflectionClass(Engine::class);
        $prop = $reflection->getProperty('counter');
        $prop->setAccessible(true);
        $prop->setValue($engine, $counter);
        $prop->setAccessible(false);

        return [$engine, $counter];
    }

    public function getDi(array $tests = [])
    {
        $di = $this->createMock(DiInterface::class);
        $config = $this->createMock(Config::class);
        $abTestConfig = $this->createMock(Config::class);
        $di
            ->expects($this->any())
            ->method('get')
            ->with('config')
            ->willReturn($config);
        $config
            ->expects($this->any())
            ->method('get')
            ->with('ab_test', $this->isInstanceOf(Config::class))
            ->willReturn($abTestConfig);
        $abTestConfig
            ->expects($this->any())
            ->method('toArray')
            ->willReturnCallback(function () use (&$tests) {
                return $tests;
            });

        return $di;
    }
}
