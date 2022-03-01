<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\PostDeploy;

use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Step\PostDeploy\CleanCache;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Shell\MagentoShell;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class CleanCacheTest extends TestCase
{
    /**
     * @var CleanCache
     */
    private $step;

    /**
     * @var MagentoShell|MockObject
     */
    private $magentoShellMock;

    /**
     * @var DeployInterface|MockObject
     */
    private $stageConfig;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->magentoShellMock = $this->createMock(MagentoShell::class);
        $this->stageConfig = $this->getMockForAbstractClass(DeployInterface::class);
        /** @var ShellFactory|MockObject $shellFactoryMock */
        $shellFactoryMock = $this->createMock(ShellFactory::class);
        $shellFactoryMock->expects($this->once())
            ->method('createMagento')
            ->willReturn($this->magentoShellMock);
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();

        $this->step = new CleanCache(
            $shellFactoryMock,
            $this->stageConfig,
            117,
            $this->loggerMock
        );
    }

    /**
     * @throws StepException
     */
    public function testExecute()
    {
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Flushing cache.'],
                ['Cache flushed successfully.']
            );
        $this->stageConfig->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_VERBOSE_COMMANDS)
            ->willReturn('-vvv');
        $this->magentoShellMock->expects($this->once())
            ->method('execute')
            ->with('cache:flush', ['-vvv']);

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithShellException()
    {
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('Some error');
        $this->expectExceptionCode(117);

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Flushing cache.');
        $this->stageConfig->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_VERBOSE_COMMANDS)
            ->willReturn('-vvv');
        $this->magentoShellMock->expects($this->once())
            ->method('execute')
            ->willThrowException(new ShellException('Some error'));

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithConfigException()
    {
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('Some error');
        $this->expectExceptionCode(15);

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Flushing cache.');
        $this->stageConfig->expects($this->once())
            ->method('get')
            ->willThrowException(new ConfigException('Some error', 15));

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithPostDeployHook()
    {
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Flushing cache.'],
                ['Cache flushed successfully.']
            );
        $this->stageConfig->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_VERBOSE_COMMANDS)
            ->willReturn('-vvv');
        $this->magentoShellMock->expects($this->once())
            ->method('execute')
            ->with('cache:flush', ['-vvv']);

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteNoVerbosity()
    {
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Flushing cache.'],
                ['Cache flushed successfully.']
            );
        $this->stageConfig->expects($this->once())
            ->method('get')
            ->willReturn('');
        $this->magentoShellMock->expects($this->once())
            ->method('execute')
            ->with('cache:flush');

        $this->step->execute();
    }
}
