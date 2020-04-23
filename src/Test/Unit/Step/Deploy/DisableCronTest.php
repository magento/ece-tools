<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy;

use Magento\MagentoCloud\Cron\Switcher;
use Magento\MagentoCloud\Step\Deploy\BackgroundProcessKill;
use Magento\MagentoCloud\Step\Deploy\DisableCron;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Test class for Magento\MagentoCloud\Process\Deploy\DisableCron
 */
class DisableCronTest extends TestCase
{
    /**
     * @var DisableCron
     */
    private $step;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var Switcher|MockObject
     */
    private $cronSwitcherMock;

    /**
     * @var BackgroundProcessKill|MockObject
     */
    private $backgroundProcessKillMock;

    /**
     * Setup the test environment.
     */
    protected function setUp()
    {
        $this->backgroundProcessKillMock = $this->createMock(BackgroundProcessKill::class);
        $this->cronSwitcherMock = $this->createMock(Switcher::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);

        $this->step = new DisableCron(
            $this->backgroundProcessKillMock,
            $this->cronSwitcherMock,
            $this->loggerMock
        );
    }

    public function testExecute()
    {
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Disable cron');
        $this->cronSwitcherMock->expects($this->once())
            ->method('disable');
        $this->backgroundProcessKillMock->expects($this->once())
            ->method('execute');
        $this->step->execute();
    }
}
