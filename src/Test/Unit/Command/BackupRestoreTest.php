<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Command;

use Magento\MagentoCloud\Command\BackupRestore;
use Magento\MagentoCloud\Command\Backup\Restore;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;

/**
 * @inheritdoc
 */
class BackupRestoreTest extends TestCase
{
    /**
     * @var Restore|MockObject
     */
    private $restoreMock;

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
     * @var BackupRestore
     */
    private $command;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->restoreMock = $this->createMock(Restore::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->questionMock = $this->getMockBuilder(QuestionHelper::class)
                                ->onlyMethods(['ask'])
                                ->getMock();
        $this->helperSetMock = $this->createMock(HelperSet::class);

        $this->command = new BackupRestore($this->restoreMock, $this->loggerMock);
        $this->command->setHelperSet($this->helperSetMock);
    }

    /**
     * Test execute method.
     *
     * @param int $askExpected
     * @param bool $askAnswer
     * @param array $options
     * @param int $runExpected
     * @dataProvider executeDataProvider
     */
    #[DataProvider('executeDataProvider')]
    public function testExecute(int $askExpected, bool $askAnswer, array $options, int $runExpected): void
    {
        $this->helperSetMock->expects($this->exactly($askExpected))
            ->method('get')
            ->with('question')
            ->willReturn($this->questionMock);
        $this->questionMock->expects($this->exactly($askExpected))
            ->method('ask')
            ->willReturn($askAnswer);
        $this->restoreMock->expects($this->exactly($runExpected))
            ->method('run');
        $this->loggerMock->expects($this->never())
            ->method('critical');
        $tester = new CommandTester($this->command);
        $tester->execute($options);
        $this->assertSame(0, $tester->getStatusCode());
    }

    /**
     * Execute data provider method.
     *
     * @return array
     */
    public static function executeDataProvider(): array
    {
        return [
            ['askExpected' => 0, 'askAnswer' => true, 'options' => [], 'runExpected' => 1],
            ['askExpected' => 0, 'askAnswer' => false, 'options' => [], 'runExpected' => 1],
            ['askExpected' => 1, 'askAnswer' => false, 'options' => ['-f' => true], 'runExpected' => 0],
            ['askExpected' => 1, 'askAnswer' => true, 'options' => ['-f' => true], 'runExpected' => 1],
            ['askExpected' => 1, 'askAnswer' => false, 'options' => ['--force' => true], 'runExpected' => 0],
            ['askExpected' => 1, 'askAnswer' => true, 'options' => ['--force' => true], 'runExpected' => 1],
        ];
    }

    public function testExecuteWithException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Sorry error');

        $this->helperSetMock->expects($this->never())
            ->method('get')
            ->with('question')
            ->willReturn($this->questionMock);
        $this->questionMock->expects($this->never())
            ->method('ask');
        $this->restoreMock->expects($this->once())
            ->method('run')
            ->willThrowException(new \Exception('Sorry error'));
        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with('Sorry error');
        $tester = new CommandTester($this->command);
        $tester->execute([]);
        $this->assertSame(1, $tester->getStatusCode());
    }
}
