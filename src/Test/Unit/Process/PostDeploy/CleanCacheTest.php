<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\PostDeploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Process\PostDeploy\CleanCache;
use Magento\MagentoCloud\Shell\ShellInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

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
     * @var ShellInterface|Mock
     */
    private $shellMock;

    /**
     * @var DeployInterface|Mock
     */
    private $stageConfig;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->shellMock = $this->getMockForAbstractClass(ShellInterface::class);
        $this->stageConfig = $this->getMockForAbstractClass(DeployInterface::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->environmentMock = $this->createMock(Environment::class);

        $this->process = new CleanCache(
            $this->shellMock,
            $this->stageConfig,
            $this->loggerMock,
            $this->environmentMock
        );
    }

    public function testExecute()
    {
        $this->stageConfig->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_VERBOSE_COMMANDS)
            ->willReturn('-vvv');
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('php ./bin/magento cache:flush -vvv');
        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with('Your application seems not using \'post_deploy\' hook.');
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Clearing application cache.');

        $this->process->execute();
    }

    public function testExecuteWithPostDeployHook()
    {
        $this->environmentMock->expects($this->once())
            ->method('getApplication')
            ->willReturn([
                'hooks' => ['post_deploy' => []],
            ]);
        $this->stageConfig->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_VERBOSE_COMMANDS)
            ->willReturn('-vvv');
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('php ./bin/magento cache:flush -vvv');
        $this->loggerMock->expects($this->never())
            ->method('warning');
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Clearing application cache.');

        $this->process->execute();
    }

    public function testExecuteNoVerbosity()
    {
        $this->stageConfig->expects($this->once())
            ->method('get')
            ->willReturn('');
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('php ./bin/magento cache:flush ');

        $this->process->execute();
    }
}
