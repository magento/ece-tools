<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Build;

use Magento\MagentoCloud\Filesystem\FlagFileInterface;
use Magento\MagentoCloud\Filesystem\FlagFilePool;
use Magento\MagentoCloud\Process\Build\CompressStaticContent;
use Magento\MagentoCloud\Util\StaticContentCompressor;
use Magento\MagentoCloud\Config\Stage\BuildInterface;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * Unit test for build-time static content compressor.
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
     * @var BuildInterface|Mock
     */
    private $stageConfigMock;

    /**
     * @var StaticContentCompressor|Mock
     */
    private $compressorMock;

    /**
     * @var FlagFilePool|Mock
     */
    private $flagFilePoolMock;

    /**
     * @var FlagFileInterface|Mock
     */
    private $flagMock;

    /**
     * Setup the test environment.
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->stageConfigMock = $this->getMockBuilder(BuildInterface::class)
            ->getMockForAbstractClass();
        $this->compressorMock = $this->createMock(StaticContentCompressor::class);
        $this->flagFilePoolMock = $this->createMock(FlagFilePool::class);
        $this->flagMock = $this->getMockBuilder(FlagFileInterface::class)
            ->getMockForAbstractClass();

        $this->process = new CompressStaticContent(
            $this->loggerMock,
            $this->compressorMock,
            $this->flagFilePoolMock,
            $this->stageConfigMock
        );
    }

    /**
     * Test build-time compression.
     */
    public function testExecute()
    {
        $this->flagFilePoolMock->expects($this->once())
            ->method('getFlag')
            ->with('scd_in_build')
            ->willReturn($this->flagMock);
        $this->flagMock->expects($this->once())
            ->method('exists')
            ->willReturn(true);
        $this->stageConfigMock->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [BuildInterface::VAR_SCD_COMPRESSION_LEVEL, 6],
                [BuildInterface::VAR_VERBOSE_COMMANDS, ''],
            ]);
        $this->compressorMock
            ->expects($this->once())
            ->method('process')
            ->with(6);

        $this->process->execute();
    }

    /**
     * Test that build-time compression will fail appropriately.
     */
    public function testExecuteNoCompress()
    {
        $this->flagFilePoolMock->expects($this->once())
            ->method('getFlag')
            ->with('scd_in_build')
            ->willReturn($this->flagMock);
        $this->flagMock->expects($this->once())
            ->method('exists')
            ->willReturn(false);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with(
                'Skipping build-time static content compression because static content deployment hasn\'t happened.'
            );
        $this->stageConfigMock->expects($this->never())
            ->method('get');
        $this->compressorMock
            ->expects($this->never())
            ->method('process');

        $this->process->execute();
    }
}
