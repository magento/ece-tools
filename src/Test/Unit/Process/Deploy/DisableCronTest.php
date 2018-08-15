<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy;

use Magento\MagentoCloud\Config\Deploy\Writer;
use Magento\MagentoCloud\Process\Deploy\CronProcessKill;
use Magento\MagentoCloud\Process\Deploy\DisableCron;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Test class for Magento\MagentoCloud\Process\Deploy\DisableCron
 */
class DisableCronTest extends TestCase
{
    /**
     * @var DisableCron
     */
    private $process;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var Writer|MockObject
     */
    private $writerMock;

    /**
     * @var CronProcessKill|MockObject
     */
    private $cronProcessKillMock;

    /**
     * Setup the test environment.
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->cronProcessKillMock = $this->createMock(CronProcessKill::class);
        $this->writerMock = $this->createMock(Writer::class);

        $this->process = new DisableCron(
            $this->cronProcessKillMock,
            $this->loggerMock,
            $this->writerMock
        );
    }

    public function testExecute()
    {
        $config = ['cron' => ['enabled' => 0]];
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Disable cron');
        $this->writerMock->expects($this->once())
            ->method('update')
            ->with($config);
        $this->cronProcessKillMock->expects($this->once())
            ->method('execute');
        $this->process->execute();
    }
}
