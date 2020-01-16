<?php

namespace ABTesting\Chooser;

use ABTesting\Test\Test;
use ABTesting\Test\Variant;

class PercentChooser implements ChooserInterface
{
    public const DEFAULT_PERCENT = 50;

    private $floor;

    /**
     * PercentChooser constructor.
     * @param int $floor
     */
    public function __construct(int $floor = self::DEFAULT_PERCENT)
    {
        $this->floor = min($floor, 100);
    }

    public function choose(Test $test): ?Variant
    {
        if (mt_rand(0, 100) >= $this->floor) {
            return $test->getVariants()[1] ?? null;
        }

        return  $test->getVariants()[0] ?? null;
    }

    public function isCountable(Test $test, string $action): bool
    {
        return true;
    }
}
