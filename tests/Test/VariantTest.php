<?php

namespace ABTesting\Tests\Test;

use ABTesting\Test\Variant;
use PHPUnit\Framework\TestCase;

class VariantTest extends TestCase
{
    public function testGetters() {
        $variant = new Variant('phpunit', 'with any value');
        $this->assertEquals('phpunit', $variant->getIdentifier());
        $this->assertEquals('with any value', $variant->getValue());
        $this->assertFalse($variant->isDefault());
    }
}
