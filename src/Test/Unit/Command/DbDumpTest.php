<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Command;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Command\DbDump;
use Magento\MagentoCloud\DB\DumpProcessor;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @inheritdoc
 */
#[AllowMockObjectsWithoutExpectations]
class DbDumpTest extends TestCase
{
    /**
     * @var DbDump
     */
    private $command;

    /**
     * @var DumpProcessor|MockObject
     */
    private $dumpProcessorMock;

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
    protected function setUp(): void
    {
        $this->dumpProcessorMock = $this->createMock(DumpProcessor::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->questionMock = $this->getMockBuilder(QuestionHelper::class)
            ->onlyMethods(['ask'])
            ->getMock();
        $this->helperSetMock = $this->createMock(HelperSet::class);

        $this->command = new DbDump(
            $this->dumpProcessorMock,
            $this->loggerMock
        );

        $this->command->setHelperSet($this->helperSetMock);
    }

    /**
     * Test execute method with confirmation.
     *
     * @return void
     */
    public function testExecuteWithConfirmation(): void
    {
        $this->helperSetMock->expects($this->once())
            ->method('get')
            ->with('question')
            ->willReturn($this->questionMock);
        $this->questionMock->expects($this->once())
            ->method('ask')
            ->willReturn(true);
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->willReturnCallback(function (string $axis) {
                static $series = [
                    'Starting backup.',
                    'Backup completed.',
                ];

                $this->assertSame(array_shift($series), $axis);
            });
        $this->dumpProcessorMock->expects($this->once())
            ->method('execute')
            ->with(false, []);

        $tester = new CommandTester(
            $this->command
        );
        $tester->execute([]);
        $this->assertSame(0, $tester->getStatusCode());
    }

    /**
     * Test execute method with confirmation deny.
     *
     * @return void
     */
    public function testExecuteConfirmationDeny(): void
    {
        $this->helperSetMock->expects($this->once())
            ->method('get')
            ->with('question')
            ->willReturn($this->questionMock);
        $this->questionMock->expects($this->once())
            ->method('ask')
            ->willReturn(false);
        $this->loggerMock->expects($this->never())
            ->method('info');
        $this->dumpProcessorMock->expects($this->never())
            ->method('execute');

        $tester = new CommandTester(
            $this->command
        );
        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
    }

    /**
     * Test execute method with removing definers.
     *
     * @param array $options
     * @dataProvider executeWithRemovingDefinersDataProvider
     * @return void
     */
    #[DataProvider('executeWithRemovingDefinersDataProvider')]
    public function testExecuteWithRemovingDefiners(array $options)
    {
        $this->helperSetMock->expects($this->once())
            ->method('get')
            ->with('question')
            ->willReturn($this->questionMock);
        $this->questionMock->expects($this->once())
            ->method('ask')
            ->willReturn(true);
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->willReturnCallback(function (string $axis) {
                static $series = [
                    'Starting backup.',
                    'Backup completed.',
                ];

                $this->assertSame(array_shift($series), $axis);
            });
        $this->dumpProcessorMock->expects($this->once())
            ->method('execute')
            ->with(true, []);

        $tester = new CommandTester(
            $this->command
        );
        $tester->execute($options);

        $this->assertSame(0, $tester->getStatusCode());
    }

    /**
     * Test execute method with removing definers data provider.
     *
     * @return array
     */
    public static function executeWithRemovingDefinersDataProvider(): array
    {
        return [
            [['--' . DbDump::OPTION_REMOVE_DEFINERS => true]],
            [['-d' => true]],
        ];
    }

    /**
     * Test execute method with exception.
     *
     * @return void
     */
    public function testExecuteWithException(): void
    {
        $this->helperSetMock->expects($this->once())
            ->method('get')
            ->with('question')
            ->willReturn($this->questionMock);
        $this->questionMock->expects($this->once())
            ->method('ask')
            ->willReturn(true);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Starting backup.');
        $this->dumpProcessorMock->expects($this->once())
            ->method('execute')
            ->willThrowException(new \Exception('Some error'));
        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with('Some error');

        $tester = new CommandTester(
            $this->command
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Some error');

        $tester->execute([]);
    }

    /**
     * Test execute method with databases.
     *
     * @return void
     */
    public function testExecuteWithDatabases(): void
    {
        $this->helperSetMock->expects($this->once())
            ->method('get')
            ->with('question')
            ->willReturn($this->questionMock);
        $this->questionMock->expects($this->once())
            ->method('ask')
            ->willReturn(true);
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->willReturnCallback(function (string $axis) {
                static $series = [
                    'Starting backup.',
                    'Backup completed.',
                ];

                $this->assertSame(array_shift($series), $axis);
            });
        $this->dumpProcessorMock->expects($this->once())
            ->method('execute')
            ->with(false, ['main', 'sales', 'quote']);

        $tester = new CommandTester(
            $this->command
        );
        $tester->execute([DbDump::ARGUMENT_DATABASES => ['main', 'sales', 'quote']]);
    }

    /**
     * Test execute method with invalid databases.
     *
     * @return void
     */
    public function testExecuteWithInvalidDatabases(): void
    {
        $exceptionMessage = 'Incorrect the database names: [ invalidName0 invalidName1 invalidName2 ].'
            . ' Available database names: [ main quote sales ]';
        $this->dumpProcessorMock->expects($this->never())
            ->method('execute');
        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with($exceptionMessage);

        $this->expectException(GenericException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $tester = new CommandTester(
            $this->command
        );
        $tester->execute([DbDump::ARGUMENT_DATABASES => ['invalidName0', 'invalidName1', 'invalidName2']]);
    }
}
