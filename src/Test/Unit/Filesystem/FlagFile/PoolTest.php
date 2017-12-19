<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Filesystem\Flag;

use Magento\MagentoCloud\Filesystem\Flag\FlagInterface;
use Magento\MagentoCloud\Filesystem\Flag\Pool;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use PHPUnit\Framework\TestCase;

class PoolTest extends TestCase
{
    /**
     * @var FlagInterface|Mock
     */
    private $flagMockOneMock;

    /**
     * @var FlagInterface|Mock
     */
    private $flagMockTwoMock;

    /**
     * @var Pool
     */
    private $pool;

    protected function setUp()
    {
        $this->flagMockOneMock = $this->getMockForAbstractClass(FlagInterface::class);
        $this->flagMockTwoMock = $this->getMockForAbstractClass(FlagInterface::class);

        $this->pool = new Pool([
            $this->flagMockOneMock,
            $this->flagMockTwoMock,
        ]);
    }

    public function testGet()
    {
        $this->flagMockOneMock->expects($this->any())
            ->method('getKey')
            ->willReturn('key1');
        $this->flagMockTwoMock->expects($this->any())
            ->method('getKey')
            ->willReturn('key2');

        $flags = $this->pool->get(['key2']);

        $this->assertCount(1, $flags);
        $this->assertEquals(
            $this->flagMockTwoMock,
            array_pop($flags)
        );
    }

    public function testGetWithoutFilter()
    {
        $this->assertCount(2, $this->pool->get());
    }
}
