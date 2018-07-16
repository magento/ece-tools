<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\Update;

use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\Update\CleanConfigCache;
use Magento\MagentoCloud\Shell\ShellInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class CleanConfigCacheTest extends TestCase
{
    /**
     * @var CleanConfigCache
     */
    private $process;

    /**
     * @var DeployInterface|Mock
     */
    private $stageConfigMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var ShellInterface|Mock
     */
    private $shellMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->shellMock = $this->getMockForAbstractClass(ShellInterface::class);

        $this->process = new CleanConfigCache(
            $this->stageConfigMock,
            $this->loggerMock,
            $this->shellMock
        );
    }

    public function testExecute()
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_VERBOSE_COMMANDS)
            ->willReturn('someValue');
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Clean configuration cache');
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('php ./bin/magento cache:flush config someValue');

        $this->process->execute();
    }
}
