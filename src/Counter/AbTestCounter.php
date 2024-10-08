<?php

namespace ABTesting\Counter;

use Phalcon\Config\Config;
use Phalcon\Di\Injectable;

/**
 * Class AbTestCounter
 *
 * @property Config $config
 * @property \Redis $cache
 */
class AbTestCounter extends Injectable
{
    private array $alreadyTested = [];

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
        $iterator = null;
        return $this->cache->hScan($testName, $iterator, $restriction);
    }
}
