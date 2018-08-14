<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Build;

use Magento\MagentoCloud\Config\Stage\BuildInterface;
use Magento\MagentoCloud\Process\Build\CompileDi;
use Magento\MagentoCloud\Shell\ExecBinMagento;
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
     * @var ExecBinMagento|MockObject
     */
    private $shellMock;

    /**
     * @var BuildInterface|MockObject
     */
    private $stageConfigMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->shellMock = $this->createMock(ExecBinMagento::class);
        $this->stageConfigMock = $this->getMockBuilder(BuildInterface::class)
            ->getMockForAbstractClass();

        $this->process = new CompileDi(
            $this->loggerMock,
            $this->shellMock,
            $this->stageConfigMock
        );
    }

    public function testExecute()
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(BuildInterface::VAR_VERBOSE_COMMANDS)
            ->willReturn('-vvv');
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Running DI compilation');
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('setup:di:compile', '-vvv');

        $this->process->execute();
    }
}
