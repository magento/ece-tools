<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\PostDeploy;

use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Process\PostDeploy\CleanCache;
use Magento\MagentoCloud\Process\ProcessException;
use Magento\MagentoCloud\Shell\MagentoShell;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class CleanCacheTest extends TestCase
{
    /**
     * @var CleanCache
     */
    private $process;

    /**
     * @var MagentoShell|MockObject
     */
    private $magentoShellMock;

    /**
     * @var DeployInterface|MockObject
     */
    private $stageConfig;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->magentoShellMock = $this->createMock(MagentoShell::class);
        $this->stageConfig = $this->getMockForAbstractClass(DeployInterface::class);
        /** @var ShellFactory|MockObject $shellFactoryMock */
        $shellFactoryMock = $this->createMock(ShellFactory::class);
        $shellFactoryMock->expects($this->once())
            ->method('createMagento')
            ->willReturn($this->magentoShellMock);

        $this->process = new CleanCache(
            $shellFactoryMock,
            $this->stageConfig
        );
    }

    /**
     * @throws ProcessException
     */
    public function testExecute()
    {
        $this->stageConfig->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_VERBOSE_COMMANDS)
            ->willReturn('-vvv');
        $this->magentoShellMock->expects($this->once())
            ->method('execute')
            ->with('cache:flush', ['-vvv']);

        $this->process->execute();
    }

    /**
     * @expectedException \Magento\MagentoCloud\Process\ProcessException
     * @expectedExceptionMessage Some error
     *
     * @throws ProcessException
     */
    public function testExecuteWithException()
    {
        $this->stageConfig->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_VERBOSE_COMMANDS)
            ->willReturn('-vvv');
        $this->magentoShellMock->expects($this->once())
            ->method('execute')
            ->willThrowException(new ShellException('Some error'));

        $this->process->execute();
    }

    /**
     * @throws ProcessException
     */
    public function testExecuteWithPostDeployHook()
    {
        $this->stageConfig->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_VERBOSE_COMMANDS)
            ->willReturn('-vvv');
        $this->magentoShellMock->expects($this->once())
            ->method('execute')
            ->with('cache:flush', ['-vvv']);

        $this->process->execute();
    }

    /**
     * @throws ProcessException
     */
    public function testExecuteNoVerbosity()
    {
        $this->stageConfig->expects($this->once())
            ->method('get')
            ->willReturn('');
        $this->magentoShellMock->expects($this->once())
            ->method('execute')
            ->with('cache:flush');

        $this->process->execute();
    }
}
