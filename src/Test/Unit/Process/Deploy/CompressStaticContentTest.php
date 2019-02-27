<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy;

use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Process\Deploy\CompressStaticContent;
use Magento\MagentoCloud\Util\StaticContentCompressor;
use Magento\MagentoCloud\Config\GlobalSection as GlobalConfig;
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
     * @var StaticContentCompressor|Mock
     */
    private $compressorMock;

    /**
     * @var FlagManager|Mock
     */
    private $flagManagerMock;

    /**
     * @var DeployInterface|Mock
     */
    private $stageConfigMock;

    /**
     * @var GlobalConfig|Mock
     */
    private $globalConfigMock;

    /**
     * Setup the test environment.
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->compressorMock = $this->createMock(StaticContentCompressor::class);
        $this->flagManagerMock = $this->createMock(FlagManager::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);
        $this->globalConfigMock = $this->createMock(GlobalConfig::class);

        $this->process = new CompressStaticContent(
            $this->loggerMock,
            $this->compressorMock,
            $this->flagManagerMock,
            $this->stageConfigMock,
            $this->globalConfigMock
        );
    }

    /**
     * Test deploy-time compression.
     */
    public function testExecute()
    {
        $this->globalConfigMock->expects($this->once())
            ->method('get')
            ->with(GlobalConfig::VAR_SCD_ON_DEMAND)
            ->willReturn(false);
        $this->stageConfigMock->expects($this->exactly(4))
            ->method('get')
            ->willReturnMap([
                [DeployInterface::VAR_SCD_COMPRESSION_LEVEL, 4],
                [DeployInterface::VAR_SCD_COMPRESSION_TIMEOUT, 500],
                [DeployInterface::VAR_SKIP_SCD, false],
                [DeployInterface::VAR_VERBOSE_COMMANDS, ''],
            ]);
        $this->flagManagerMock
            ->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD)
            ->willReturn(false);
        $this->compressorMock
            ->expects($this->once())
            ->method('process')
            ->with(4, 500);

        $this->process->execute();
    }

    /**
     * Test deploy-time compression is skipped.
     */
    public function testExecuteSkipped()
    {
        $this->globalConfigMock->expects($this->once())
            ->method('get')
            ->with(GlobalConfig::VAR_SCD_ON_DEMAND)
            ->willReturn(true);
        $this->stageConfigMock->expects($this->never())
            ->method('get');
        $this->flagManagerMock->expects($this->never())
            ->method('exists');
        $this->compressorMock
            ->expects($this->never())
            ->method('process');
        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with('Skipping static content compression. SCD on demand is enabled.');

        $this->process->execute();
    }

    /**
     * Test that deploy-time compression will fail appropriately.
     */
    public function testExecuteNoCompressByEnv()
    {
        $this->globalConfigMock->expects($this->once())
            ->method('get')
            ->with(GlobalConfig::VAR_SCD_ON_DEMAND)
            ->willReturn(false);
        $this->flagManagerMock
            ->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD)
            ->willReturn(true);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with(
                'Static content deployment was performed during the build phase or disabled. Skipping deploy phase'
                . ' static content compression.'
            );
        $this->compressorMock
            ->expects($this->never())
            ->method('process');

        $this->process->execute();
    }
}
