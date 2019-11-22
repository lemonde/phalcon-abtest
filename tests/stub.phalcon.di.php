<?php

namespace Phalcon;

class Di implements \Phalcon\DiInterface
{
    /**
     * List of registered services
     */
    protected $_services;

    /**
     * List of shared instances
     */
    protected $_sharedInstances;

    /**
     * To know if the latest resolved instance was shared or not
     */
    protected $_freshInstance = false;

    /**
     * Events Manager
     *
     * @var \Phalcon\Events\ManagerInterface
     */
    protected $_eventsManager;

    /**
     * Latest DI build
     */
    static protected $_default;


    /**
     * Phalcon\Di constructor
     */
    public function __construct()
    {
    }

    /**
     * @param \Phalcon\Events\ManagerInterface $eventsManager
     */
    public function setInternalEventsManager(\Phalcon\Events\ManagerInterface $eventsManager)
    {
    }

    /**
     * @return \Phalcon\Events\ManagerInterface
     */
    public function getInternalEventsManager()
    {
    }

    /**
     * @param string $name
     * @param mixed $definition
     * @param bool $shared
     * @return \Phalcon\Di\ServiceInterface
     */
    public function set($name, $definition, $shared = false)
    {
    }

    /**
     * @param string $name
     * @param mixed $definition
     * @return \Phalcon\Di\ServiceInterface
     */
    public function setShared($name, $definition)
    {
    }

    /**
     * @param string $name
     */
    public function remove($name)
    {
    }

    /**
     * @param string $name
     * @param mixed $definition
     * @param bool $shared
     * @return bool|\Phalcon\Di\ServiceInterface
     */
    public function attempt($name, $definition, $shared = false)
    {
    }

    /**
     * @param string $name
     * @param \Phalcon\Di\ServiceInterface $rawDefinition
     * @return \Phalcon\Di\ServiceInterface
     */
    public function setRaw($name, \Phalcon\Di\ServiceInterface $rawDefinition)
    {
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getRaw($name)
    {
    }

    /**
     * @param string $name
     * @return \Phalcon\Di\ServiceInterface
     */
    public function getService($name)
    {
    }

    /**
     * @param string $name
     * @param mixed $parameters
     * @return mixed
     */
    public function get($name, $parameters = null)
    {
    }

    /**
     * @param string $name
     * @param array $parameters
     * @return mixed
     */
    public function getShared($name, $parameters = null)
    {
    }

    /**
     * @param string $name
     * @return bool
     */
    public function has($name)
    {
    }

    /**
     * @return bool
     */
    public function wasFreshInstance()
    {
    }

    /**
     * @return \Phalcon\Di\Service[]
     */
    public function getServices()
    {
    }

    /**
     * @param mixed $name
     * @return bool
     */
    public function offsetExists($name)
    {
    }

    /**
     * @param mixed $name
     * @param mixed $definition
     * @return bool
     */
    public function offsetSet($name, $definition)
    {
    }

    /**
     * @param mixed $name
     * @return mixed
     */
    public function offsetGet($name)
    {
    }

    /**
     * @param mixed $name
     * @return bool
     */
    public function offsetUnset($name)
    {
    }

    /**
     * @param string $method
     * @param mixed $arguments
     * @return mixed|null
     */
    public function __call($method, $arguments = null)
    {
    }

    /**
     * @param \Phalcon\Di\ServiceProviderInterface $provider
     */
    public function register(\Phalcon\Di\ServiceProviderInterface $provider)
    {
    }

    /**
     * @param \Phalcon\DiInterface $dependencyInjector
     */
    public static function setDefault(\Phalcon\DiInterface $dependencyInjector)
    {
    }

    /**
     * @return null|\Phalcon\DiInterface
     */
    public static function getDefault()
    {
        return self::$_default;
    }

    /**
     * Resets the internal default DI
     */
    public static function reset()
    {
    }

    /**
     * @param string $filePath
     * @param array $callbacks
     */
    public function loadFromYaml($filePath, array $callbacks = null)
    {
    }

    /**
     * @param string $filePath
     */
    public function loadFromPhp($filePath)
    {
    }

    /**
     * @param \Phalcon\Config $config
     */
    protected function loadFromConfig(\Phalcon\Config $config)
    {
    }

}
