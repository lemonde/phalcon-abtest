<?php

namespace ABTesting\Traits;

trait ScanableRedis
{
    /**
     * @return \Redis
     * @codeCoverageIgnore
     */
    private function getRedis()
    {
        if (!$this->_redis instanceof \Redis) {
            $this->_connect();
        }

        return $this->_redis;
    }

    /**
     * @param string $key
     * @param string $hashKey
     * @param int $value
     * @return int
     */
    public function hIncrBy($key, $hashKey, $value)
    {
        return $this->getRedis()->hIncrBy($key, $hashKey, $value);
    }

    /**
     * @param string $key
     * @param string $pattern
     * @param int $count
     * @return array
     */
    public function hScan($key, $pattern = null, $count = 0)
    {
        $iterator = null;
        $results = [];
        $this->getRedis()->setOption(\Redis::OPT_SCAN, \Redis::SCAN_RETRY);

        do {
            $arr_keys = $this->getRedis()->hScan($key, $iterator, $pattern, $count);

            if (!$arr_keys) {
                break;
            }

            foreach ($arr_keys as $str_field => $str_value) {
                $results[$str_field] = $str_value;
            }
        } while ($arr_keys);

        return $results;
    }
}
