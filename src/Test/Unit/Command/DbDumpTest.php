<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Command;

use Magento\MagentoCloud\Command\DbDump;
use Magento\MagentoCloud\DB\DumpGenerator;
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
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->dumpGeneratorMock = $this->createMock(DumpGenerator::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);

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
            $this->loggerMock
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

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Some error
     */
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
        $this->dumpGeneratorMock->expects($this->once())
            ->method('create')
            ->willThrowException(new \Exception('Some error'));

        $tester = new CommandTester(
            $this->command
        );
        $tester->execute([]);
    }
}
