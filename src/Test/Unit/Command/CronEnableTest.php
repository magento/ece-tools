<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Command;

use Magento\MagentoCloud\Command\CronEnable;
use Magento\MagentoCloud\Cron\Switcher;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @inheritDoc
 */
class CronEnableTest extends TestCase
{
    /**
     * @var CronEnable
     */
    private $command;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var Switcher|MockObject
     */
    private $cronSwitcherMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->cronSwitcherMock = $this->createMock(Switcher::class);

        $this->command = new CronEnable($this->cronSwitcherMock, $this->loggerMock);
    }

    /**
     * @inheritDoc
     */
    public function testExecute()
    {
        $this->cronSwitcherMock->expects($this->once())
            ->method('enable');
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Enable cron');
        $this->loggerMock->expects($this->never())
            ->method('critical');

        $tester = new CommandTester($this->command);
        $tester->execute([]);
        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testExecuteWithException()
    {
        $this->expectException(FileSystemException::class);
        $this->expectExceptionMessage('save error');

        $this->cronSwitcherMock->expects($this->once())
            ->method('enable')
            ->willThrowException(new FileSystemException('save error'));
        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with('save error');

        $tester = new CommandTester($this->command);
        $tester->execute([]);
        $this->assertSame(1, $tester->getStatusCode());
    }
}
