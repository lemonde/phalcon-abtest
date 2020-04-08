<?php

namespace ABTesting\Tests\Chooser;

use ABTesting\Chooser\MultiplePercentChooser;
use ABTesting\Test\Test;
use ABTesting\Test\Variant;
use PHPUnit\Framework\TestCase;

class MultiplePercentChooserTest extends TestCase
{
    public function testChoose()
    {
        $chooser = new MultiplePercentChooser([70, 10, 10, 10,], 100);
        $variantA = new Variant('A', 'A');
        $variantB = new Variant('B', 'B');
        $variantC = new Variant('C', 'C');
        $variantD = new Variant('D', 'D');
        $variantDefault = new Variant('Default','should never occurs');

        $test = new Test('test', [$variantA, $variantB, $variantC, $variantD], $variantDefault);

        mt_srand(0);
        $this->assertContains($chooser->choose($test)->getIdentifier(), ['A', 'B', 'C', 'D']);
        $this->assertFalse($chooser->choose($test)->getIdentifier() === 'Default');
    }
}
