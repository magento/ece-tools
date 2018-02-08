<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Command;

use Magento\MagentoCloud\Command\BackupRestore;
use Magento\MagentoCloud\Command\Backup\Restore;
use Psr\Log\LoggerInterface;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;

/**
 * @inheritdoc
 */
class BackupRestoreTest extends TestCase
{
    /**
     * @var Restore|Mock
     */
    private $restoreMock;

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
     * @var BackupRestore
     */
    private $command;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->restoreMock = $this->createMock(Restore::class);
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->questionMock = $this->getMockBuilder(QuestionHelper::class)
            ->setMethods(['ask'])
            ->getMock();
        $this->helperSetMock = $this->createMock(HelperSet::class);

        $this->command = new BackupRestore($this->restoreMock, $this->loggerMock);
        $this->command->setHelperSet($this->helperSetMock);
    }

    /**
     * @param int $askExpected
     * @param bool $askAnswer
     * @param array $options
     * @param int $runExpected
     * @dataProvider executeDataProvider
     */
    public function testExecute(int $askExpected, bool $askAnswer, array $options, int $runExpected)
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
     * @return array
     */
    public function executeDataProvider(): array
    {
        return [
            ['askExpected' => 0,'askAnswer' => true, 'options' => [], 'runExpected' => 1],
            ['askExpected' => 0,'askAnswer' => false, 'options' => [], 'runExpected' => 1],
            ['askExpected' => 1,'askAnswer' => false, 'options' => ['-f' => true], 'runExpected' => 0],
            ['askExpected' => 1,'askAnswer' => true, 'options' => ['-f' => true], 'runExpected' => 1],
            ['askExpected' => 1,'askAnswer' => false, 'options' => ['--force' => true], 'runExpected' => 0],
            ['askExpected' => 1,'askAnswer' => true, 'options' => ['--force' => true], 'runExpected' => 1],
        ];
    }

    /**
     * @expectedExceptionMessage Sorry error
     * @expectedException \Exception
     */
    public function testExecuteWithException()
    {
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
