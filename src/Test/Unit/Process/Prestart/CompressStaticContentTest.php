<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Prestart;

use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Process\Prestart\CompressStaticContent;
use Magento\MagentoCloud\Util\StaticContentCompressor;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

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
     * @var FlagManager|Mock
     */
    private $flagManagerMock;

    /**
     * @var DeployInterface|Mock
     */
    private $stageConfigMock;

    /**
     * Setup the test environment.
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->environmentMock = $this->createMock(Environment::class);
        $this->compressorMock = $this->createMock(StaticContentCompressor::class);
        $this->flagManagerMock = $this->createMock(FlagManager::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);

        $this->process = new CompressStaticContent(
            $this->loggerMock,
            $this->environmentMock,
            $this->compressorMock,
            $this->flagManagerMock,
            $this->stageConfigMock
        );
    }

    public function testExecuteCompressInPrestart()
    {
        $this->environmentMock
            ->expects($this->once())
            ->method('isDeployStaticContent')
            ->willReturn(true);
        $this->flagManagerMock->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_PENDING)
            ->willReturn(true);
        $this->stageConfigMock->expects($this->exactly(3))
            ->method('get')
            ->willReturnMap([
                [DeployInterface::VAR_SKIP_SCD, false],
                [DeployInterface::VAR_SCD_COMPRESSION_LEVEL, 4],
                [DeployInterface::VAR_VERBOSE_COMMANDS, ''],
            ]);

        $this->process->execute();
    }

    public function testExecuteSCDInBuild()
    {
        $this->environmentMock
            ->expects($this->once())
            ->method('isDeployStaticContent')
            ->willReturn(false);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Static content deployment was performed during the build phase or disabled. Skipping prestart phase'
                . ' static content compression.');
        $this->flagManagerMock->expects($this->never())
            ->method('exists');
        $this->compressorMock
            ->expects($this->never())
            ->method('process');

        $this->process->execute();
    }

    public function testExecuteSCDInDeploy()
    {
        $this->environmentMock
            ->expects($this->once())
            ->method('isDeployStaticContent')
            ->willReturn(true);
        $this->flagManagerMock->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_PENDING)
            ->willReturn(false);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Static content deployment was performed during the build phase or disabled. Skipping prestart phase'
                . ' static content compression.');
        $this->compressorMock
            ->expects($this->never())
            ->method('process');

        $this->process->execute();
    }
}
