<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy;

use Magento\MagentoCloud\Filesystem\FlagFileInterface;
use Magento\MagentoCloud\Filesystem\FlagFilePool;
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
     * @var FlagFilePool|Mock
     */
    private $flagFilePoolMock;

    /**
     * @var FlagFileInterface
     */
    private $flagMock;

    /**
     * Setup the test environment.
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->environmentMock = $this->createMock(Environment::class);
        $this->compressorMock = $this->createMock(StaticContentCompressor::class);
        $this->flagFilePoolMock = $this->createMock(FlagFilePool::class);
        $this->flagMock = $this->getMockBuilder(FlagFileInterface::class)
            ->getMockForAbstractClass();

        $this->process = new CompressStaticContent(
            $this->loggerMock,
            $this->environmentMock,
            $this->compressorMock,
            $this->flagFilePoolMock
        );
    }

    /**
     * Test deploy-time compression.
     */
    public function testExecute()
    {
        $this->environmentMock->expects($this->once())
            ->method('getVariable')
            ->with(Environment::VAR_SCD_COMPRESSION_LEVEL, CompressStaticContent::COMPRESSION_LEVEL)
            ->willReturn(4);
        $this->environmentMock
            ->expects($this->once())
            ->method('isDeployStaticContent')
            ->willReturn(true);
        $this->flagFilePoolMock->expects($this->once())
            ->method('getFlag')
            ->with('scd_pending')
            ->willReturn($this->flagMock);
        $this->flagMock->expects($this->once())
            ->method('exists')
            ->willReturn(false);
        $this->environmentMock->expects($this->once())
            ->method('getVerbosityLevel')
            ->willReturn('');
        $this->compressorMock
            ->expects($this->once())
            ->method('process')
            ->with(4);

        $this->process->execute();
    }

    /**
     * Test that deploy-time compression will fail appropriately.
     */
    public function testExecuteNoCompressByEnv()
    {
        $this->environmentMock
            ->expects($this->once())
            ->method('isDeployStaticContent')
            ->willReturn(false);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with(
                'Static content deployment was performed during the build phase or disabled. Skipping deploy phase'
                . ' static content compression.'
            );
        $this->flagFilePoolMock->expects($this->never())
            ->method('getFlag');
        $this->environmentMock->expects($this->never())
            ->method('getVerbosityLevel');
        $this->compressorMock
            ->expects($this->never())
            ->method('process');

        $this->process->execute();
    }

    public function testExecuteNoCompressBySCDInBuild()
    {
        $this->environmentMock
            ->expects($this->once())
            ->method('isDeployStaticContent')
            ->willReturn(true);
        $this->flagFilePoolMock->expects($this->once())
            ->method('getFlag')
            ->with('scd_pending')
            ->willReturn($this->flagMock);
        $this->flagMock->expects($this->once())
            ->method('exists')
            ->willReturn(true);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Postpone static content compression until prestart');
        $this->environmentMock->expects($this->never())
            ->method('getVerbosityLevel');
        $this->compressorMock
            ->expects($this->never())
            ->method('process');

        $this->process->execute();
    }
}
