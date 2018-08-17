<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Util;

use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Util\MaintenanceModeSwitcher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class MaintenanceModeSwitcherTest extends TestCase
{
    /**
     * @var ShellInterface|MockObject
     */
    private $shellMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var DeployInterface|MockObject
     */
    private $stageConfigMock;

    /**
     * @var MaintenanceModeSwitcher
     */
    private $maintenanceModeSwitcher;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->shellMock = $this->getMockForAbstractClass(ShellInterface::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);

        $this->maintenanceModeSwitcher = new MaintenanceModeSwitcher(
            $this->shellMock,
            $this->loggerMock,
            $this->stageConfigMock
        );
    }

    public function testEnable()
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_VERBOSE_COMMANDS)
            ->willReturn('-v');
        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with('Enabling Maintenance mode');
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('php ./bin/magento maintenance:enable --ansi --no-interaction -v');

        $this->maintenanceModeSwitcher->enable();
    }

    public function testDisable()
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_VERBOSE_COMMANDS)
            ->willReturn('-v');
        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with('Maintenance mode is disabled.');
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('php ./bin/magento maintenance:disable --ansi --no-interaction -v');

        $this->maintenanceModeSwitcher->disable();
    }
}
