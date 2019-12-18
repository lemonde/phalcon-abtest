<?php

namespace ABTesting\Tests\Traits;

use PHPUnit\Framework\MockObject\MockObject;
use Redis;
use ABTesting\Tests\TestCase;
use ABTesting\Traits\ScanableRedis;

class ScanableRedisTest extends TestCase
{

    public function testHIncrBy()
    {
        /** @var ScanableRedis|MockObject $mockTrait */
        $mockTrait = $this->getMockBuilder(ScanableRedis::class)->setMethods([])->getMockForTrait();
        $mockTrait->_redis = $this->createMock(Redis::class);
        $mockTrait->_redis->expects($this->once())->method('hIncrBy')->with('test key', 'hash key', 1)->willReturn(1);

        $this->assertEquals(1, $mockTrait->hIncrBy('test key', 'hash key', 1));
    }

    public function testHScan()
    {
        /** @var ScanableRedis|MockObject $mockTrait */
        $mockTrait = $this->getMockBuilder(ScanableRedis::class)->setMethods([])->getMockForTrait();
        $mockTrait->_redis = $this->createMock(Redis::class);
        $mockTrait->_redis
            ->expects($this->atLeastOnce())
            ->method('hScan')
            ->with('test key', null, null, 0)
            ->willReturnOnConsecutiveCalls(['field' => 'value'], ['field2' => 'value'], []);

        $this->assertSame(['field' => 'value', 'field2' => 'value'], $mockTrait->hScan('test key'));
    }
}
