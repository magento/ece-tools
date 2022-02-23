<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Command;

use Magento\MagentoCloud\Command\CronDisable;
use Magento\MagentoCloud\Cron\Switcher;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Util\BackgroundProcess;
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
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var Switcher|MockObject
     */
    private $cronSwitcherMock;

    /**
     * @var BackgroundProcess|MockObject
     */
    private $backgroundProcessMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->cronSwitcherMock = $this->createMock(Switcher::class);
        $this->backgroundProcessMock = $this->createMock(BackgroundProcess::class);

        $this->command = new CronDisable(
            $this->cronSwitcherMock,
            $this->backgroundProcessMock,
            $this->loggerMock
        );
    }

    /**
     * @inheritDoc
     */
    public function testExecute()
    {
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Disable cron');
        $this->loggerMock->expects($this->never())
            ->method('critical');
        $this->cronSwitcherMock->expects($this->once())
            ->method('disable');
        $this->backgroundProcessMock->expects($this->once())
            ->method('kill');

        $tester = new CommandTester($this->command);
        $tester->execute([]);
        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testExecuteWithException()
    {
        $this->expectException(FileSystemException::class);
        $this->expectExceptionMessage('save error');

        $this->cronSwitcherMock->expects($this->once())
            ->method('disable')
            ->willThrowException(new FileSystemException('save error'));
        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with('save error');

        $tester = new CommandTester($this->command);
        $tester->execute([]);
        $this->assertSame(1, $tester->getStatusCode());
    }
}
