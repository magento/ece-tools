<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Command;

use Magento\MagentoCloud\App\Command\Wrapper;
use Magento\MagentoCloud\Command\DbDump;
use Magento\MagentoCloud\Process\ProcessInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use PHPUnit_Framework_MockObject_MockObject as Mock;

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
     * @var ProcessInterface|Mock
     */
    private $processMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var HelperSet|Mock
     */
    private $helperSetMock;

    /**
     * @var QuestionHelper|Mock
     */
    private $questionMock;

    /**
     * @var Wrapper|Mock
     */
    private $wrapperMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->processMock = $this->getMockForAbstractClass(ProcessInterface::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->wrapperMock = $this->createTestProxy(Wrapper::class, [$this->loggerMock]);
        $this->questionMock = $this->getMockBuilder(QuestionHelper::class)
            ->setMethods(['ask'])
            ->getMock();
        $this->helperSetMock = $this->createMock(HelperSet::class);

        $this->helperSetMock->expects($this->once())
            ->method('get')
            ->with('question')
            ->willReturn($this->questionMock);

        $this->command = new DbDump(
            $this->processMock,
            $this->loggerMock,
            $this->wrapperMock
        );
        $this->command->setHelperSet($this->helperSetMock);
    }

    public function testExecuteWithConfirmation()
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
        $this->processMock->expects($this->once())
            ->method('execute');

        $tester = new CommandTester(
            $this->command
        );
        $tester->execute([]);

        $this->assertSame(Wrapper::CODE_SUCCESS, $tester->getStatusCode());
    }

    public function testExecuteConfirmationDeny()
    {
        $this->questionMock->expects($this->once())
            ->method('ask')
            ->willReturn(false);

        $this->loggerMock->expects($this->never())
            ->method('info');
        $this->processMock->expects($this->never())
            ->method('execute');

        $tester = new CommandTester(
            $this->command
        );
        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testExecuteWithException()
    {
        $this->questionMock->expects($this->once())
            ->method('ask')
            ->willReturn(true);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Starting backup.');
        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with('Some error');
        $this->processMock->expects($this->once())
            ->method('execute')
            ->willThrowException(new \Exception('Some error'));

        $tester = new CommandTester(
            $this->command
        );
        $tester->execute([]);

        $this->assertSame(Wrapper::CODE_FAILURE, $tester->getStatusCode());
        $this->assertContains('Some error', $tester->getDisplay());
    }
}
