<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy;

use Magento\MagentoCloud\Process\Deploy\CompressStaticContent;
use Magento\MagentoCloud\Util\StaticContentCompressor;
use Magento\MagentoCloud\Config\Environment;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * Unit test for deploy-time static content compressor.
 */
class CompressStaticContentTest extends TestCase
{
    /**
     * @var CompressStaticContent
     */
    private $process;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @var StaticContentCompressor|Mock
     */
    private $compressorMock;

    /**
     * Setup the test environment.
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->environmentMock = $this->getMockBuilder(Environment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->compressorMock = $this->getMockBuilder(StaticContentCompressor::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->process = new CompressStaticContent(
            $this->loggerMock,
            $this->environmentMock,
            $this->compressorMock
        );
    }

    /**
     * Test deploy-time compression.
     */
    public function testExecute()
    {
        $this->environmentMock
            ->expects($this->once())
            ->method('isDeployStaticContent')
            ->willReturn(true);
        $this->compressorMock
            ->expects($this->once())
            ->method('process')
            ->with('4');

        $this->process->execute();
    }

    /**
     * Test that deploy-time compression will fail appropriately.
     */
    public function testExecuteNoCompress()
    {
        $this->environmentMock
            ->expects($this->once())
            ->method('isDeployStaticContent')
            ->willReturn(false);
        $this->compressorMock
            ->expects($this->never())
            ->method('process');

        $this->process->execute();
    }
}
