<?php

namespace ABTesting\Tests\Counter;

use ABTesting\Counter\AbTestCounter;
use PHPUnit\Framework\TestCase;
use Redis;

class AbTestCounterTest extends TestCase
{
    public function testSaveCounter()
    {
        $counter = new AbTestCounter();
        $counter->cache = $this->createMock(Redis::class);
        $counter->cache
            ->expects($this->once())
            ->method('hIncrBy')
            ->with(
                'test',
                $this->logicalAnd(
                    $this->stringContains('template'),
                    $this->stringContains('device'),
                    $this->stringContains('type'),
                    $this->stringContains(date('YmdH'))
                )
            );


        $counter->saveCounter('type', 'device', 'test', 'template');
    }

    public function testGetCount()
    {
        $testName = 'test';
        $restriction = 'restriction';
        $counter = new AbTestCounter();
        $counter->cache = $this->createMock(Redis::class);
        $counter->cache
            ->expects($this->once())
            ->method('hScan')
            ->with($testName, $restriction)
            ->willReturn([]);


        $this->assertSame([], $counter->getCount($testName, $restriction));
    }
}
