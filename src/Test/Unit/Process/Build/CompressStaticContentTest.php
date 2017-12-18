<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Build;

use Magento\MagentoCloud\Config\Build as BuildConfig;
use Magento\MagentoCloud\Filesystem\FlagFile\Flag\StaticContentDeployInBuild;
use Magento\MagentoCloud\Filesystem\FlagFile\Manager as FlagFileManager;
use Magento\MagentoCloud\Process\Build\CompressStaticContent;
use Magento\MagentoCloud\Util\StaticContentCompressor;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

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
     * @var BuildConfig|Mock
     */
    private $buildConfigMock;

    /**
     * @var StaticContentCompressor|Mock
     */
    private $compressorMock;

    /**
     * @var FlagFileManager|Mock
     */
    private $flagFileManagerMock;

    /**
     * Setup the test environment.
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->buildConfigMock = $this->createMock(BuildConfig::class);
        $this->compressorMock = $this->createMock(StaticContentCompressor::class);
        $this->flagFileManagerMock = $this->createMock(FlagFileManager::class);

        $this->process = new CompressStaticContent(
            $this->loggerMock,
            $this->buildConfigMock,
            $this->compressorMock,
            $this->flagFileManagerMock
        );
    }

    /**
     * Test build-time compression.
     */
    public function testExecute()
    {
        $this->flagFileManagerMock->expects($this->once())
            ->method('exists')
            ->with(StaticContentDeployInBuild::KEY)
            ->willReturn(true);
        $this->buildConfigMock->expects($this->once())
            ->method('getVerbosityLevel')
            ->willReturn('');
        $this->buildConfigMock->expects($this->once())
            ->method('get')
            ->with(BuildConfig::OPT_SCD_COMPRESSION_LEVEL, CompressStaticContent::COMPRESSION_LEVEL)
            ->willReturn(6);
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
        $this->flagFileManagerMock->expects($this->once())
            ->method('exists')
            ->with(StaticContentDeployInBuild::KEY)
            ->willReturn(false);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with(
                'Skipping build-time static content compression because static content deployment hasn\'t happened.'
            );
        $this->buildConfigMock->expects($this->never())
            ->method('getVerbosityLevel');
        $this->compressorMock
            ->expects($this->never())
            ->method('process');

        $this->process->execute();
    }
}
