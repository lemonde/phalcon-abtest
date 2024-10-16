<?php

namespace ABTesting\Volt;

use ABTesting\Engine;
use ABTesting\Exception\AbTestingException;
use Phalcon\Di\Di;

class ABTestingExtension
{
    public function compileFunction(string $name, string $arguments): ?string
    {
        return match ($name) {
            'ab_test_result' => self::class . '::getTestResult(' . $arguments . ')',
            'ab_test_click' => self::class . '::getTestClick(' . $arguments . ')',
            'ab_test_href' => self::class . '::getTestHref(' . $arguments . ')',
            default => null,
        };
    }

    public static function getTestResult(string $testName): ?string
    {
        $engine  = Engine::getInstance();

        try {
            $test = $engine->getTest($testName);

            if (empty($test)) {
                return null;
            }

            if (!$engine->isActivated()) {
                return $test->getDefaultVariant()->getValue();
            }

            return $test->getWinner()->getValue();
        } catch (\Throwable $t) {
            if (null !== $engine->getEventsManager()) {
                $e = new AbTestingException('Unable to get test result.', 0, $t);
                $engine->getEventsManager()->fire('abtest:beforeException', Engine::getInstance(), $e);
            }

            return null;
        }
    }

    public static function getTestClick(string $testName, string $target, ?string $winnerName = null): ?string
    {
        $engine  = Engine::getInstance();

        try {

            if (!$engine->isActivated()) {
                return $target;
            }

            $test = $engine->getTest($testName);

            if (empty($test)) {
                return $target;
            }

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

            if ($path !== $target) {
                $engine->savePrint($test->getIdentifier(), $winner->getIdentifier());
            }

            return $path;
        } catch (\Throwable $t) {
            if (null !== $engine->getEventsManager()) {
                $e = new AbTestingException('Unable to get test counter url.', 0, $t);
                $engine->getEventsManager()->fire('abtest:beforeException', Engine::getInstance(), $e);
            }

            return $target;
        }
    }

    public static function getTestHref(string $testName, string $target, ?string $winnerName = null): string
    {
        $counterLink = self::getTestClick($testName, $target, $winnerName);
        $attributes = 'href="' . htmlspecialchars($target, ENT_QUOTES) . '" ';
        $attributes .= ' onmousedown="' . htmlspecialchars("this.href = '$counterLink'", ENT_COMPAT) . '" ';

        return $attributes;
    }
}
