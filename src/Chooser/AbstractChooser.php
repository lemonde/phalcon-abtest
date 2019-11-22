<?php

namespace ABTesting\Chooser;

use ABTesting\Test\Variant;

abstract class AbstractChooser
{
    abstract public function choose(array $variants): ?Variant;
}
