<?php

namespace ABTesting;

use ABTesting\Counter\AbTestCounter;
use ABTesting\Exception\AbTestingException;
use ABTesting\Test\Test;
use ABTesting\Test\Variant;
use Detection\MobileDetect;
use Phalcon\Config;
use Phalcon\Di;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\DiInterface;
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
    public static function getInstance(?DiInterface $di = null)
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
        $tests = $this->getDI()->get('config')->get('ab_test', new Config([]))->toArray();

        $detect = new MobileDetect();

        $this->device = 'desktop';

        if ($detect->isTablet()) {
            $this->device = 'tablet';
        } elseif ($detect->isMobile()) {
            $this->device = 'mobile';
        }

        foreach ($tests as $identifier => $definition) {
            $test = new Test($identifier);
            $defaultVariant = $definition['default'];
            foreach ($definition['variants'] as $key => $variantDefinition) {
                $variant = new Variant($key, $variantDefinition, (string) $defaultVariant === (string) $key);
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
     * @return Test
     * @throws AbTestingException
     */
    public function getTest(string $identifier)
    {
        if (empty($this->tests[$identifier])) {
            throw new AbTestingException('Unconfigured AB test with name ' . $identifier, AbTestingException::UNDEFINED_TEST_CODE);
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

    public function savePrint(string $testName, string $template): void
    {
        if (null !== $this->getEventsManager()) {
            $this->getEventsManager()->fire('abtest:beforePrint', $this, [$testName, $template]);
        }

        $this->counter->saveCounter('print', $this->device, $testName, $template);
    }

    public function saveClick(string $testName, string $template): void
    {
        if (null !== $this->getEventsManager()) {
            $this->getEventsManager()->fire('abtest:beforeClick', $this, [$testName, $template]);
        }

        $this->counter->saveCounter('click', $this->device, $testName, $template);
    }

    /**
     * @param ManagerInterface $eventsManager
     * @return $this
     */
    public function setEventsManager(ManagerInterface $eventsManager)
    {
        $this->eventsManager = $eventsManager;
        return $this;
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
     * @return Engine
     */
    public function setDI(DiInterface $dependencyInjector)
    {
        $this->dependencyInjector = $dependencyInjector;
        return $this;
    }

    /**
     * Returns the internal dependency injector
     *
     * @return null|DiInterface
     */
    public function getDI()
    {
        return $this->dependencyInjector;
    }

    /**
     * Returns the internal event manager
     *
     * @return null|ManagerInterface
     */
    public function getEventsManager()
    {
        return $this->eventsManager;
    }
}
