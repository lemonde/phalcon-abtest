<?php

namespace ABTesting\Chooser;

use ABTesting\Test\Test;
use ABTesting\Test\Variant;

class MultiplePercentChooser implements ChooserInterface
{
    /** @var array */
    private $limits = [];

    /**
     * PercentChooser constructor.
     * @param int $floor
     */
    public function __construct(array $percents)
    {
        foreach ($percents as $idx => $percent) {
            $range = $idx === 0 ? [0] : array_slice($percents, 0, $idx);
            $percentMin = array_sum($range);
            $percentMax = $idx === 0 ? $percent : array_sum($range) + $percent;
            $this->limits[] = ['min' => $percentMin, 'max' => $percentMax];
        }
    }

    public function choose(Test $test): ?Variant
    {
        $pull = mt_rand(0, 100);
        foreach ($this->limits as $idx => $limit) {
            if ($pull >= $limit['min'] && $pull <= $limit['max']) {
                return $test->getVariants()[$idx] ?? null;
            }
        }

        return  $test->getDefaultVariant();
    }

    public function isCountable(Test $test, string $action): bool
    {
        return true;
    }
}
