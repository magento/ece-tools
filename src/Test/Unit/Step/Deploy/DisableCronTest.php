<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy;

use Magento\MagentoCloud\Config\Magento\Env\WriterInterface;
use Magento\MagentoCloud\Step\Deploy\BackgroundProcessKill;
use Magento\MagentoCloud\Step\Deploy\DisableCron;
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
    private $step;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var WriterInterface|MockObject
     */
    private $writerMock;

    /**
     * @var BackgroundProcessKill|MockObject
     */
    private $backgroundProcessKillMock;

    /**
     * Setup the test environment.
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->backgroundProcessKillMock = $this->createMock(BackgroundProcessKill::class);
        $this->writerMock = $this->getMockForAbstractClass(WriterInterface::class);

        $this->step = new DisableCron(
            $this->backgroundProcessKillMock,
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
        $this->backgroundProcessKillMock->expects($this->once())
            ->method('execute');
        $this->step->execute();
    }
}
