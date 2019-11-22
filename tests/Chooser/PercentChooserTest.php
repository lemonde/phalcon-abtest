<?php

namespace ABTesting\Tests\Chooser;

use ABTesting\Chooser\PercentChooser;
use ABTesting\Test\Test;
use ABTesting\Test\Variant;
use PHPUnit\Framework\TestCase;

class PercentChooserTest extends TestCase
{

    public function testChoose()
    {
        $chooser = new PercentChooser(100);
        $variantA = new Variant('A', 'A');
        $variantB = new Variant('B', 'B');
        $test = new Test('test', [$variantA, $variantB]);

        mt_srand(0);
        $this->assertEquals('A', $chooser->choose($test)->getIdentifier());
        $chooser = new PercentChooser(0);

        mt_srand(0);
        $this->assertEquals('B', $chooser->choose($test)->getIdentifier());
    }
}
