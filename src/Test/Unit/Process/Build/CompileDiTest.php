<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Build;

use Magento\MagentoCloud\Config\Stage\BuildInterface;
use Magento\MagentoCloud\Process\Build\CompileDi;
use Magento\MagentoCloud\Shell\MagentoShell;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class CompileDiTest extends TestCase
{
    /**
     * @var CompileDi
     */
    private $process;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var MagentoShell|MockObject
     */
    private $magentoShellMock;

    /**
     * @var BuildInterface|MockObject
     */
    private $stageConfigMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->magentoShellMock = $this->createMock(MagentoShell::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(BuildInterface::class);

        $this->process = new CompileDi(
            $this->loggerMock,
            $this->magentoShellMock,
            $this->stageConfigMock
        );
    }

    public function testExecute()
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(BuildInterface::VAR_VERBOSE_COMMANDS)
            ->willReturn('-vvv');
        $this->loggerMock->expects($this->exactly(2))
            ->method('notice')
            ->withConsecutive(
                ['Running DI compilation'],
                ['End of running DI compilation']
            );
        $this->magentoShellMock->expects($this->once())
            ->method('execute')
            ->with('setup:di:compile', ['-vvv']);

        $this->process->execute();
    }

    public function testExecuteWithEmptyArgument()
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(BuildInterface::VAR_VERBOSE_COMMANDS)
            ->willReturn('');
        $this->loggerMock->expects($this->exactly(2))
            ->method('notice')
            ->withConsecutive(
                ['Running DI compilation'],
                ['End of running DI compilation']
            );
        $this->magentoShellMock->expects($this->once())
            ->method('execute')
            ->with('setup:di:compile', []);

        $this->process->execute();
    }
}
