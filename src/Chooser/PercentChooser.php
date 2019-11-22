<?php

namespace ABTesting\Chooser;

use ABTesting\Test\Variant;

class PercentChooser extends AbstractChooser
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

    public function choose(array $variants): ?Variant
    {
        if (mt_rand(0, 100) >= $this->floor) {
            return $variants[1] ?? null;
        }

        return  $variants[0] ?? null;
    }
}
