<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Build;

use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Process\Build\CompressStaticContent;
use Magento\MagentoCloud\Process\ProcessException;
use Magento\MagentoCloud\Util\StaticContentCompressor;
use Magento\MagentoCloud\Config\Stage\BuildInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

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
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var BuildInterface|MockObject
     */
    private $stageConfigMock;

    /**
     * @var StaticContentCompressor|MockObject
     */
    private $compressorMock;

    /**
     * @var FlagManager|MockObject
     */
    private $flagManagerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(BuildInterface::class);
        $this->compressorMock = $this->createMock(StaticContentCompressor::class);
        $this->flagManagerMock = $this->createMock(FlagManager::class);

        $this->process = new CompressStaticContent(
            $this->loggerMock,
            $this->compressorMock,
            $this->flagManagerMock,
            $this->stageConfigMock
        );
    }

    /**
     * Test build-time compression.
     *
     * @throws ProcessException
     */
    public function testExecute()
    {
        $this->flagManagerMock->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD)
            ->willReturn(true);
        $this->stageConfigMock->expects($this->exactly(3))
            ->method('get')
            ->willReturnMap([
                [BuildInterface::VAR_SCD_COMPRESSION_LEVEL, 6],
                [BuildInterface::VAR_SCD_COMPRESSION_TIMEOUT, 500],
                [BuildInterface::VAR_VERBOSE_COMMANDS, ''],
            ]);
        $this->compressorMock
            ->expects($this->once())
            ->method('process')
            ->with(6, 500);

        $this->process->execute();
    }

    /**
     * Test that build-time compression will fail appropriately.
     *
     * @throws ProcessException
     */
    public function testExecuteNoCompress()
    {
        $this->flagManagerMock->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD)
            ->willReturn(false);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with(
                'Skipping build-time static content compression because static content deployment has not happened.'
            );
        $this->stageConfigMock->expects($this->never())
            ->method('get');
        $this->compressorMock
            ->expects($this->never())
            ->method('process');

        $this->process->execute();
    }
}
