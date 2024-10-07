<?php

namespace ABTesting;

use ABTesting\Counter\AbTestCounter;
use ABTesting\Exception\AbTestingException;
use ABTesting\DeviceProvider\DeviceProviderInterface;
use ABTesting\Test\Test;
use ABTesting\Test\Variant;
use Phalcon\Config\Config;
use Phalcon\Di\Di;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\Di\DiInterface;
use Phalcon\Events\EventsAwareInterface;
use Phalcon\Events\ManagerInterface;

/**
 * Class Engine
 *
 * @property-read Config $config
 */
class Engine implements InjectionAwareInterface, EventsAwareInterface
{
    /**
     * @var self
     */
    private static $instance;

    /**
     * @var null|DiInterface
     */
    private $dependencyInjector;

    /**
     * @var null|ManagerInterface
     */
    private $eventsManager;

    /**
     * @param DiInterface|null $di
     * @return Engine
     */
    public static function getInstance(?DiInterface $di = null): Engine
    {
        if (empty(self::$instance)) {
            self::$instance = new self($di ?? Di::getDefault());
        }
        return self::$instance;
    }

    /**
     * @var Test[]
     */
    private $tests = [];

    /**
     * @var AbTestCounter
     */
    private $counter;

    /**
     * @var string
     */
    private $device;

    /**
     * @var bool
     */
    private $activated = true;

    /**
     * Engine constructor.
     * @param DiInterface $di
     */
    private function __construct(DiInterface $di)
    {
        $this->counter = new AbTestCounter();
        $this->setDI($di);

        $tests = $this->getDI()->get('phalcon-abtest.tests')->toArray();

        $this->setDevice('desktop');

        if ($this->getDI()->has('phalcon-abtest.device_provider')) {
            $deviceProvider = $this->getDI()->get('phalcon-abtest.device_provider');

            if (!$deviceProvider instanceof DeviceProviderInterface) {
                throw new AbTestingException('Device provider must be an instance of ' . DeviceProviderInterface::class);
            }

            $this->setDevice($deviceProvider->getDevice());
        }

        foreach ($tests as $identifier => $definition) {
            $test = new Test($identifier);
            $defaultVariant = $definition['default'];
            foreach ($definition['variants'] as $key => $variantDefinition) {
                $variant = new Variant($key, $variantDefinition);
                $test->addVariant($variant);
            }
            if (empty($test->getDefaultVariant())) {
                $variant = new Variant('__default', $defaultVariant, true);
                $test->setDefaultVariant($variant);
            }

            if (!empty($definition['chooser'])) {
                if (is_string($definition['chooser'])) {
                    $test->setChooser(new $definition['chooser']());
                } else {
                    $class = $definition['chooser'][0];
                    $params = array_slice((array) $definition['chooser'], 1) ?? [];
                    $test->setChooser(new $class(...$params));
                }
            }
            $this->addTest($test);
        }
    }
    /**
     * @param Test $test
     */
    public function addTest(Test $test)
    {
        $this->tests[$test->getIdentifier()] = $test;
    }

    /**
     * @param string $identifier
     * @return null|Test
     */
    public function getTest(string $identifier): ?Test
    {
        if (empty($this->tests[$identifier])) {
            if (null !== $this->getEventsManager()) {
                $e = new AbTestingException('Unconfigured AB test with name ' . $identifier, AbTestingException::UNDEFINED_TEST_CODE);
                $this->getEventsManager()->fire('abtest:beforeException', $this, $e);
            }

            return null;
        }

        return $this->tests[$identifier];
    }
    /**
     * @return Test[]
     */
    public function getTests(): array
    {
        return $this->tests;
    }

    /**
     * @return string
     */
    public function getDevice(): string
    {
        return $this->device;
    }

    /**
     * @param string $device
     * @return Engine
     */
    public function setDevice(string $device): Engine
    {
        $this->device = $device;
        return $this;
    }

    public function savePrint(string $testName, string $template): void
    {
        if (null !== ($test = $this->getTest($testName)) && $test->getChooser()->isCountable($test, 'print')) {
            if (null !== $this->getEventsManager()) {
                $this->getEventsManager()->fire('abtest:beforePrint', $this, [$testName, $template]);
            }

            $this->counter->saveCounter('print', $this->getDevice(), $testName, $template);
        }
    }

    public function saveClick(string $testName, string $template): void
    {
        if (null !== ($test = $this->getTest($testName)) && $test->getChooser()->isCountable($test, 'click')) {
            if (null !== $this->getEventsManager()) {
                $this->getEventsManager()->fire('abtest:beforeClick', $this, [$testName, $template]);
            }

            $this->counter->saveCounter('click', $this->getDevice(), $testName, $template);
        }
    }

    /**
     * @param ManagerInterface $eventsManager
     * @return void
     */
    public function setEventsManager(ManagerInterface $eventsManager): void
    {
        $this->eventsManager = $eventsManager;
    }

    /**
     * @return $this
     */
    public function activate(): self
    {
        $this->activated = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function deactivate(): self
    {
        $this->activated = false;
        return $this;
    }

    /**
     * @return bool
     */
    public function isActivated(): bool
    {
        return $this->activated;
    }

    /**
     * @return AbTestCounter
     */
    public function getCounter(): AbTestCounter
    {
        return $this->counter;
    }

    /**
     * Sets the dependency injector
     *
     * @param DiInterface $dependencyInjector
     * @return void
     */
    public function setDI(DiInterface $dependencyInjector): void
    {
        $this->dependencyInjector = $dependencyInjector;
    }

    /**
     * Returns the internal dependency injector
     *
     * @return null|DiInterface
     */
    public function getDI(): DiInterface
    {
        return $this->dependencyInjector;
    }

    /**
     * Returns the internal event manager
     *
     * @return null|ManagerInterface
     */
    public function getEventsManager(): ?ManagerInterface
    {
        return $this->eventsManager;
    }
}
