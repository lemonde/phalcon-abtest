<?php

namespace ABTesting\Counter;

use ABTesting\Engine;
use Phalcon\Cache\Backend\Redis;
use Phalcon\Config;
use Phalcon\Di\Injectable;

/**
 * Class AbTestCounter
 *
 * @property Config $config
 * @property Redis $cache
 */
class AbTestCounter extends Injectable
{
    private $alreadyTested = [];

    public function saveCounter(string $type, string $device, string $testName, string $template): void
    {
        if (!isset($this->alreadyTested[$testName])) {
            $fieldName = substr(date('YmdHi'), 0, -1) . ':' . $template . ':' . $device . ':' . $type;
            $this->cache->hIncrBy($testName, $fieldName, 1);
            $this->alreadyTested[$testName] = true;
        }
    }

    public function getCount(string $testName, string $restriction): array
    {
        return $this->cache->hScan($testName, $restriction);
    }
}
