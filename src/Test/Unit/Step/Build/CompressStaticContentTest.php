<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Build;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\Stage\BuildInterface;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\UtilityException;
use Magento\MagentoCloud\Step\Build\CompressStaticContent;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Util\StaticContentCompressor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit test for build-time static content compressor.
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
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(BuildInterface::class);
        $this->compressorMock = $this->createMock(StaticContentCompressor::class);
        $this->flagManagerMock = $this->createMock(FlagManager::class);

        $this->step = new CompressStaticContent(
            $this->loggerMock,
            $this->compressorMock,
            $this->flagManagerMock,
            $this->stageConfigMock
        );
    }

    /**
     * Test build-time compression.
     *
     * @throws StepException
     */
    public function testExecute()
    {
        $this->prepareConfig();
        $this->compressorMock
            ->expects($this->once())
            ->method('process')
            ->with(6, 500);

        $this->step->execute();
    }

    /**
     * Test that build-time compression will fail appropriately.
     *
     * @throws StepException
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
            ->willThrowException(new UndefinedPackageException('some error', 10));

        $this->expectExceptionCode(10);
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('some error');

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithShellException()
    {
        $this->prepareConfig();
        $this->compressorMock->expects($this->once())
            ->method('process')
            ->willThrowException(new ShellException('some error'));

        $this->expectExceptionCode(Error::BUILD_SCD_COMPRESSION_FAILED);
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

        $this->expectExceptionCode(Error::BUILD_UTILITY_NOT_FOUND);
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('some error');

        $this->step->execute();
    }

    public function prepareConfig(): void
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
    }
}
