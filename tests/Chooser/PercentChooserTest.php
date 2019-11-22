<?php

namespace ABTesting\Tests\Chooser;

use ABTesting\Chooser\PercentChooser;
use ABTesting\Test\Variant;
use PHPUnit\Framework\TestCase;

class PercentChooserTest extends TestCase
{

    public function testChoose()
    {
        $chooser = new PercentChooser(100);
        $variantA = new Variant('A', 'A');
        $variantB = new Variant('B', 'B');

        mt_srand(0);
        $this->assertEquals('A', $chooser->choose([$variantA, $variantB])->getIdentifier());
        $chooser = new PercentChooser(0);

        mt_srand(0);
        $this->assertEquals('B', $chooser->choose([$variantA, $variantB])->getIdentifier());
    }
}
