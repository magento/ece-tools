<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\PostDeploy;

use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Process\PostDeploy\CleanCache;
use Magento\MagentoCloud\Shell\ExecBinMagento;
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
     * @var ExecBinMagento|MockObject
     */
    private $shellMock;

    /**
     * @var DeployInterface|MockObject
     */
    private $stageConfig;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->shellMock = $this->createMock(ExecBinMagento::class);
        $this->stageConfig = $this->getMockForAbstractClass(DeployInterface::class);

        $this->process = new CleanCache($this->shellMock, $this->stageConfig);
    }

    public function testExecute()
    {
        $this->stageConfig->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_VERBOSE_COMMANDS)
            ->willReturn('-vvv');
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('cache:flush', '-vvv');

        $this->process->execute();
    }

    public function testExecuteWithPostDeployHook()
    {
        $this->stageConfig->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_VERBOSE_COMMANDS)
            ->willReturn('-vvv');
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('cache:flush', '-vvv');

        $this->process->execute();
    }

    public function testExecuteNoVerbosity()
    {
        $this->stageConfig->expects($this->once())
            ->method('get')
            ->willReturn('');
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('cache:flush', '');

        $this->process->execute();
    }
}
