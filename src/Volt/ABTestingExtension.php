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

            if (!$engine->isActivated()) {
                return $test->getDefaultVariant()->getValue();
            }
            return $test->getWinner()->getValue();
        } catch (\Throwable $t) {
            return null;
        }
    }

    public static function getTestClick(string $testName, string $target, $winnerName = null): ?string
    {
        try {
            $engine  = Engine::getInstance();

            if (!$engine->isActivated()) {
                return $target;
            }

            $test = $engine->getTest($testName);
            $winner = null;

            if ($winnerName) {
                $winner = $test->getVariant($winnerName);
            }

            if (empty($winner)) {
                $winner = $test->getWinner();
            }

            $path = Di::getDefault()->get('url')->get([
                'for' => 'ab_test_redirect',
                'testName' => $test->getIdentifier(),
                'winner' => $winner->getIdentifier(),
            ], [
                'u' => $target
            ]);

            if (!$winnerName && (!$test->hasBattled() || $winner->isDefault())) {
                $path = $target;
            }

            $engine->savePrint($test->getIdentifier(), $winner->getIdentifier());

            return $path;
        } catch (\Throwable $t) {
            return $target;
        }
    }
}
