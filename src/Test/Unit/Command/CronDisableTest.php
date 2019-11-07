<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Command;

use Magento\MagentoCloud\Command\CronDisable;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Step\Deploy\DisableCron;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @inheritDoc
 */
class CronDisableTest extends TestCase
{
    /**
     * @var CronDisable
     */
    private $command;

    /**
     * @var DisableCron|MockObject
     */
    private $disableCronMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->disableCronMock = $this->createMock(DisableCron::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);

        $this->command = new CronDisable($this->disableCronMock, $this->loggerMock);
    }

    /**
     * @inheritDoc
     */
    public function testExecute()
    {
        $this->disableCronMock->expects($this->once())
            ->method('execute');
        $this->loggerMock->expects($this->never())
            ->method('critical');

        $tester = new CommandTester($this->command);
        $tester->execute([]);
        $this->assertSame(0, $tester->getStatusCode());
    }

    /**
     * @expectedExceptionMessage save error
     * @expectedException \Magento\MagentoCloud\Filesystem\FileSystemException
     */
    public function testExecuteWithException()
    {
        $this->disableCronMock->expects($this->once())
            ->method('execute')
            ->willThrowException(new FileSystemException('save error'));
        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with('save error');

        $tester = new CommandTester($this->command);
        $tester->execute([]);
        $this->assertSame(1, $tester->getStatusCode());
    }
}
