<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Filesystem\FlagFile;

use Magento\MagentoCloud\Filesystem\FlagFile\FlagInterface;
use Magento\MagentoCloud\Filesystem\FlagFile\Pool;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use PHPUnit\Framework\TestCase;

class PoolTest extends TestCase
{
    /**
     * @var FlagInterface|Mock
     */
    private $flagFileMockOneMock;

    /**
     * @var FlagInterface|Mock
     */
    private $flagFileMockTwoMock;

    /**
     * @var Pool
     */
    private $pool;

    protected function setUp()
    {
        $this->flagFileMockOneMock = $this->getMockForAbstractClass(FlagInterface::class);
        $this->flagFileMockTwoMock = $this->getMockForAbstractClass(FlagInterface::class);

        $this->pool = new Pool([
            $this->flagFileMockOneMock,
            $this->flagFileMockTwoMock,
        ]);
    }

    public function testGet()
    {
        $this->flagFileMockOneMock->expects($this->any())
            ->method('getKey')
            ->willReturn('key1');
        $this->flagFileMockTwoMock->expects($this->any())
            ->method('getKey')
            ->willReturn('key2');

        $flagFiles = $this->pool->get(['key2']);

        $this->assertCount(1, $flagFiles);
        $this->assertEquals(
            $this->flagFileMockTwoMock,
            array_pop($flagFiles)
        );
    }

    public function testGetWithoutFilter()
    {
        $this->assertCount(2, $this->pool->get());
    }
}
