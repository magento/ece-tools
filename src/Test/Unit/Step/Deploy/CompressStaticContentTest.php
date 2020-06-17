<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\UtilityException;
use Magento\MagentoCloud\Step\Deploy\CompressStaticContent;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Util\StaticContentCompressor;
use Magento\MagentoCloud\Config\GlobalSection as GlobalConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for deploy-time static content compressor.
 */
class CompressStaticContentTest extends TestCase
{
    /**
     * @var CompressStaticContent
     */
    private $step;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var StaticContentCompressor|MockObject
     */
    private $compressorMock;

    /**
     * @var FlagManager|MockObject
     */
    private $flagManagerMock;

    /**
     * @var DeployInterface|MockObject
     */
    private $stageConfigMock;

    /**
     * @var GlobalConfig|MockObject
     */
    private $globalConfigMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->compressorMock = $this->createMock(StaticContentCompressor::class);
        $this->flagManagerMock = $this->createMock(FlagManager::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);
        $this->globalConfigMock = $this->createMock(GlobalConfig::class);

        $this->step = new CompressStaticContent(
            $this->loggerMock,
            $this->compressorMock,
            $this->flagManagerMock,
            $this->stageConfigMock,
            $this->globalConfigMock
        );
    }

    /**
     * Test deploy-time compression.
     *
     * @throws StepException
     */
    public function testExecute(): void
    {
        $this->prepareConfig();
        $this->flagManagerMock
            ->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD)
            ->willReturn(false);
        $this->compressorMock
            ->expects($this->once())
            ->method('process')
            ->with(4, 500);

        $this->step->execute();
    }

    /**
     * Test deploy-time compression is skipped.
     *
     * @throws StepException
     */
    public function testExecuteSkipped(): void
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

        $this->step->execute();
    }

    /**
     * Test that deploy-time compression will fail appropriately.
     *
     * @throws StepException
     */
    public function testExecuteNoCompressByEnv(): void
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

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithException()
    {
        $this->prepareConfig();
        $this->compressorMock->expects($this->once())
            ->method('process')
            ->willThrowException(new ShellException('some error'));

        $this->expectExceptionCode(Error::DEPLOY_SCD_COMPRESSION_FAILED);
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('some error');

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithUtilityException()
    {
        $this->prepareConfig();
        $this->compressorMock->expects($this->once())
            ->method('process')
            ->willThrowException(new UtilityException('some error'));

        $this->expectExceptionCode(Error::DEPLOY_UTILITY_NOT_FOUND);
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('some error');

        $this->step->execute();
    }

    public function prepareConfig(): void
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
    }
}
