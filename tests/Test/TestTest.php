<?php

namespace ABTesting\Tests\Test;

use ABTesting\Chooser\ChooserInterface;
use ABTesting\Engine;
use ABTesting\Test\Test;
use ABTesting\Test\Variant;
use ABTesting\Tests\TestCase;
use Phalcon\Events\Manager;

class TestTest extends TestCase
{
    public function testGetDefaultWinner()
    {
        $defaultVariant = $this->createMock(Variant::class);
        $test = new Test('phpunit', [], $defaultVariant);
        $this->assertEquals($defaultVariant, $test->getWinner());
    }

    public function testVariants()
    {
        $variantA = new Variant('A', 'A', false);
        $variantB = new Variant('B', 'B', true);
        $variantC = new Variant('C', 'C', false);

        $test = new Test('phpunit', [$variantA, $variantB, $variantC]);

        $this->assertCount(3, $test->getVariants());
        $test->removeVariant('B');
        $this->assertCount(2, $test->getVariants());
    }

    public function testBattle()
    {
        $engine = $this->createMockForSingleton(Engine::class);
        $engine->expects($this->once())->method('isActivated')->willReturn(true);
        $engine->expects($this->exactly(4))->method('getEventsManager')->willReturn($this->createMock(Manager::class));

        $variantA = new Variant('A', 'A', false);
        $variantB = new Variant('B', 'B', false);
        $variantC = new Variant('C', 'C', false);

        $test = new Test('phpunit', [$variantA, $variantB], $variantC);

        $this->assertFalse($test->hasBattled());
        $chooser = $this->createMock(ChooserInterface::class);
        $chooser
            ->expects($this->once())
            ->method('choose')
            ->willReturn($variantA);
        $test->setChooser($chooser);
        $test->battle();
        $this->assertTrue($test->hasBattled());
        $this->assertEquals($variantA, $test->getWinner());
        $this->assertFalse($test->isDefault());
    }

    public function testBattleDeactivated()
    {
        $variantA = new Variant('A', 'A', false);
        $variantB = new Variant('B', 'B', false);
        $variantC = new Variant('C', 'C', false);

        $test = new Test('phpunit', [$variantA, $variantB], $variantC);

        $engine = $this->createMockForSingleton(Engine::class);
        $engine->expects($this->once())->method('isActivated')->willReturn(false);

        $this->assertFalse($test->hasBattled());
        $chooser = $this->createMock(ChooserInterface::class);
        $chooser
            ->expects($this->never())
            ->method('choose')
            ->willReturn($variantA);
        $test->setChooser($chooser);
        $test->battle();
        $this->assertTrue($test->hasBattled());
        $this->assertEquals($variantC, $test->getWinner());
    }
}
