<?php

namespace ABTesting\Tests;

use ABTesting\Chooser\PercentChooser;
use ABTesting\Counter\AbTestCounter;
use ABTesting\DeviceProvider\DeviceProviderInterface;
use ABTesting\Engine;
use ABTesting\Test\Test;
use Phalcon\Config\Config;
use Phalcon\DI\DiInterface;
use Phalcon\Events\Manager as EventsManager;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class EngineTest extends TestCase
{
    public function setUp(): void
    {
        unset($_SERVER['HTTP_USER_AGENT']);
        $reflection = new ReflectionClass(Engine::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true); // now we can modify that :)
        $instance->setValue(null, null); // instance is gone
        $instance->setAccessible(false); // clean up
    }

    /**
     *
     * @param string $userAgent
     *
     * @return \ABTesting\Engine
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getUserAgent')]
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

    public function testGetEmptyInstance() {
        $di = $this->getDi([]);
        $eventManager = $this->createMock(EventsManager::class);
        $engine = Engine::getInstance($di);
        $engine->setEventsManager($eventManager);

        $this->assertCount(0, $engine->getTests());
        $this->assertContainsOnlyInstancesOf(Test::class, $engine->getTests());
        $this->assertNull($engine->getTest('phpunit_ab_test'));
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

    /**
     * @throws \ReflectionException
     */
    public function testSavePrintNoTest() {
        list($engine, $counter) = $this->getEngine([]);

        $eventsManager = $this->createMock(EventsManager::class);

        $counter
            ->expects($this->never())
            ->method('saveCounter')
            ->with('print', 'desktop', 'sample_test', 'sample_template');

        $engine->setEventsManager($eventsManager);
        $engine->savePrint('sample_test', 'sample_template');
    }

    public function testSavePrint() {
        list($engine, $counter) = $this->getEngine([
            'phpunit_ab_test' => [
                'variants' => [
                    'test_A' => 'test A',
                    'test_B' => 'test B',
                ],
                'chooser' => [PercentChooser::class],
                'default' => 'test_A'
            ],
        ]);

        $eventsManager = $this->createMock(EventsManager::class);

        $counter
            ->expects($this->once())
            ->method('saveCounter')
            ->with('print', 'desktop', 'phpunit_ab_test', 'test_A');

        $engine->setEventsManager($eventsManager);
        $engine->savePrint('phpunit_ab_test', 'test_A');
    }

    public function testSaveClickNoTest() {
        list($engine, $counter) = $this->getEngine([]);

        $eventsManager = $this->createMock(EventsManager::class);

        $counter
            ->expects($this->never())
            ->method('saveCounter')
            ->with('click', 'desktop', 'sample_test', 'sample_template');

        $engine->setEventsManager($eventsManager);
        $engine->saveClick('sample_test', 'sample_template');
    }

    public function testSaveClick() {
        list($engine, $counter) = $this->getEngine([
            'phpunit_ab_test' => [
                'variants' => [
                    'test_A' => 'test A',
                    'test_B' => 'test B',
                ],
                'chooser' => [PercentChooser::class],
                'default' => 'test_A'
            ],
        ]);

        $eventsManager = $this->createMock(EventsManager::class);

        $counter
            ->expects($this->once())
            ->method('saveCounter')
            ->with('click', 'desktop', 'phpunit_ab_test', 'test_A');

        $engine->setEventsManager($eventsManager);
        $engine->saveClick('phpunit_ab_test', 'test_A');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getDataDeviceProvider')]
    public function testDeviceProvider($hasDeviceProvider, $device, $expectedDevice)
    {
        $di = $this->createMock(DiInterface::class);

        $di
            ->expects($this->any())
            ->method('has')
            ->with('phalcon-abtest.device_provider')
            ->willReturn($hasDeviceProvider);

        $di->expects($this->any())
            ->method('get')
            ->willReturnCallback(function ($name) use ($hasDeviceProvider, $device) {
                if ($name === 'phalcon-abtest.tests') {
                    return $this->createMock(Config::class);
                } elseif ($name === 'phalcon-abtest.device_provider' && $hasDeviceProvider) {
                    return $this->getDeviceProvider($device);
                }

                throw new \LogicException('Invalid call di->get("' . $name . '")');
            });

        $engine = Engine::getInstance($di);
        $this->assertEquals($expectedDevice, $engine->getDevice());
    }

    public static function getDataDeviceProvider()
    {
        return [
            [false, null, 'desktop'],
            [true, 'mobile', 'mobile'],
            [true, 'tablet', 'tablet'],
            [true, 'desktop', 'desktop'],
        ];
    }

    public static function getUserAgent(): array
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
     */
    public function getEngine(array $tests = []): array
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
        $abTestConfig = $this->createMock(Config::class);
        $di
            ->expects($this->any())
            ->method('get')
            ->with('phalcon-abtest.tests')
            ->willReturn($abTestConfig);
        $abTestConfig
            ->expects($this->any())
            ->method('toArray')
            ->willReturnCallback(function () use (&$tests) {
                return $tests;
            });

        return $di;
    }

    public function getDeviceProvider($device = null): DeviceProviderInterface
    {
        return new class ($device) implements DeviceProviderInterface {
            private $device;
            public function __construct($device)
            {
                $this->device = $device;
            }

            public function getDevice()
            {
                return $this->device;
            }
        };
    }
}
