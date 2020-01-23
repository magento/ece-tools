<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Command;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Command\DbDump;
use Magento\MagentoCloud\Cron\JobUnlocker;
use Magento\MagentoCloud\Cron\Switcher;
use Magento\MagentoCloud\DB\DumpGenerator;
use Magento\MagentoCloud\Util\BackgroundProcess;
use Magento\MagentoCloud\Util\MaintenanceModeSwitcher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;

/**
 * @inheritdoc
 */
class DbDumpTest extends TestCase
{
    /**
     * @var DbDump
     */
    private $command;

    /**
     * @var DumpGenerator|MockObject
     */
    private $dumpGeneratorMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var HelperSet|MockObject
     */
    private $helperSetMock;

    /**
     * @var QuestionHelper|MockObject
     */
    private $questionMock;

    /**
     * @var MaintenanceModeSwitcher|MockObject
     */
    private $maintenanceModeSwitcherMock;

    /**
     * @var BackgroundProcess|MockObject
     */
    private $backgroundProcessMock;

    /**
     * @var Switcher|MockObject
     */
    private $cronSwitcherMock;

    /**
     * @var JobUnlocker|MockObject
     */
    private $jobUnlockerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->dumpGeneratorMock = $this->createMock(DumpGenerator::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->maintenanceModeSwitcherMock = $this->createMock(MaintenanceModeSwitcher::class);
        $this->cronSwitcherMock = $this->createMock(Switcher::class);
        $this->backgroundProcessMock = $this->createMock(BackgroundProcess::class);
        $this->jobUnlockerMock = $this->createMock(JobUnlocker::class);

        $this->questionMock = $this->getMockBuilder(QuestionHelper::class)
            ->setMethods(['ask'])
            ->getMock();
        $this->helperSetMock = $this->createMock(HelperSet::class);
        $this->helperSetMock->expects($this->once())
            ->method('get')
            ->with('question')
            ->willReturn($this->questionMock);

        $this->command = new DbDump(
            $this->dumpGeneratorMock,
            $this->loggerMock,
            $this->maintenanceModeSwitcherMock,
            $this->cronSwitcherMock,
            $this->backgroundProcessMock,
            $this->jobUnlockerMock
        );
        $this->command->setHelperSet($this->helperSetMock);
    }

    public function testExecuteWithConfirmation()
    {
        $this->questionMock->expects($this->once())
            ->method('ask')
            ->willReturn(true);

        $this->maintenanceModeSwitcherMock->expects($this->once())
            ->method('enable');
        $this->maintenanceModeSwitcherMock->expects($this->once())
            ->method('disable');
        $this->cronSwitcherMock->expects($this->once())
            ->method('disable');
        $this->cronSwitcherMock->expects($this->once())
            ->method('enable');
        $this->backgroundProcessMock->expects($this->once())
            ->method('kill');
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Starting backup.'],
                ['Backup completed.']
            );
        $this->dumpGeneratorMock->expects($this->once())
            ->method('create')
            ->with(false);

        $tester = new CommandTester(
            $this->command
        );
        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testExecuteConfirmationDeny()
    {
        $this->questionMock->expects($this->once())
            ->method('ask')
            ->willReturn(false);

        $this->maintenanceModeSwitcherMock->expects($this->never())
            ->method('enable');
        $this->maintenanceModeSwitcherMock->expects($this->never())
            ->method('disable');
        $this->cronSwitcherMock->expects($this->never())
            ->method('enable');
        $this->backgroundProcessMock->expects($this->never())
            ->method('kill');
        $this->loggerMock->expects($this->never())
            ->method('info');
        $this->dumpGeneratorMock->expects($this->never())
            ->method('create')
            ->with(false);

        $tester = new CommandTester(
            $this->command
        );
        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
    }

    /**
     * @param array $options
     * @dataProvider executeWithRemovingDefinersDataProvider
     */
    public function testExecuteWithRemovingDefiners(array $options)
    {
        $this->questionMock->expects($this->once())
            ->method('ask')
            ->willReturn(true);

        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Starting backup.'],
                ['Backup completed.']
            );
        $this->dumpGeneratorMock->expects($this->once())
            ->method('create')
            ->with(true);

        $tester = new CommandTester(
            $this->command
        );
        $tester->execute($options);

        $this->assertSame(0, $tester->getStatusCode());
    }

    /**
     * @return array
     */
    public function executeWithRemovingDefinersDataProvider(): array
    {
        return [
            [['--' . DbDump::OPTION_REMOVE_DEFINERS => true]],
            [['-d' => true]],
        ];
    }

    public function testExecuteWithException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Some error');

        $this->questionMock->expects($this->once())
            ->method('ask')
            ->willReturn(true);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Starting backup.');
        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with('Some error');
        $this->dumpGeneratorMock->expects($this->once())
            ->method('create')
            ->willThrowException(new \Exception('Some error'));
        $this->jobUnlockerMock->expects($this->once())
            ->method('unlockAll');

        $tester = new CommandTester(
            $this->command
        );
        $tester->execute([]);
    }

    public function testExecuteMaintenanceEnableFailed()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Some error');

        $this->questionMock->expects($this->once())
            ->method('ask')
            ->willReturn(true);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Starting backup.');
        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with('Some error');
        $this->maintenanceModeSwitcherMock->expects($this->once())
            ->method('enable')
            ->willThrowException(new GenericException('Some error'));
        $this->maintenanceModeSwitcherMock->expects($this->once())
            ->method('disable');
        $this->backgroundProcessMock->expects($this->never())
            ->method('kill');
        $this->dumpGeneratorMock->expects($this->never())
            ->method('create');
        $this->cronSwitcherMock->expects($this->never())
            ->method('disable');
        $this->cronSwitcherMock->expects($this->once())
            ->method('enable');

        $tester = new CommandTester(
            $this->command
        );
        $tester->execute([]);
    }
}
