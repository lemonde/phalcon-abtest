<?php

namespace ABTesting\Volt;

use ABTesting\Engine;
use Phalcon\Di;
use Phalcon\Di\Injectable;

class ABTestingExtension
{
    public function compileFunction($name, $arguments): ?string
    {
        switch ($name) {
            case 'ab_test_result':
                return self::class . '::getTestResult(' . $arguments . ')';
            case 'ab_test_click':
                return self::class . '::getTestClick(' . $arguments . ')';
        }

        return null;
    }

    public static function getTestResult(string $testName): ?string
    {
        try {
            $engine  = Engine::getInstance();
            $test = $engine->getTest($testName);
            return $test->getWinner()->getValue();
        } catch (\Throwable $t) {
            return null;
        }
    }

    public static function getTestClick(string $testName, string $target): ?string
    {
        try {
            $engine  = Engine::getInstance();
            $test = $engine->getTest($testName);

            $path = Di::getDefault()->get('url')->get([
                'for' => 'ab_test_redirect',
                'testName' => $test->getIdentifier(),
                'winner' => $test->getWinner()->getIdentifier(),
            ], [
                'u' => $target
            ]);

            if (!$test->hasBattled() || $test->isDefault()) {
                $path = $target;
            }

            $engine->savePrint($test->getIdentifier(), $test->getWinner()->getIdentifier());

            return $path;
        } catch (\Throwable $t) {
            return null;
        }
    }
}
