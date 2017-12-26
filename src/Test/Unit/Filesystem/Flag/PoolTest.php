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
            'key1' => $this->flagMockOneMock,
            'key2' => $this->flagMockTwoMock,
        ]);
    }

    public function testGetFlagOne()
    {
        $flag = $this->pool->get('key1');

        $this->assertInstanceOf(FlagInterface::class, $flag);
    }

    public function testGetFlagTwo()
    {
        $flag = $this->pool->get('key2');

        $this->assertInstanceOf(FlagInterface::class, $flag);
    }

    public function testGetFlagNotExists()
    {
        $flag = $this->pool->get('key3');

        $this->assertNull($flag);
    }
}
