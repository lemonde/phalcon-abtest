<?php

namespace ABTesting\Chooser;

use ABTesting\Test\Test;
use ABTesting\Test\Variant;

interface ChooserInterface
{
    public function choose(Test $test): ?Variant;
    public function isCountable(Test $test, string $action): bool;
}
