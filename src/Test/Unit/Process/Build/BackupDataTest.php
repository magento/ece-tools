<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Build;

use Magento\MagentoCloud\App\Logger\Pool as LoggerPool;
use Magento\MagentoCloud\Process\Build\BackupData;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Process\Build\BackupData\StaticContent;
use Magento\MagentoCloud\Process\Build\BackupData\WritableDirectories;

/**
 * @inheritdoc
 */
class BackupDataTest extends TestCase
{
    /**
     * @var BackupData
     */
    private $process;

    /**
     * @var StaticContent|Mock
     */
    private $backupStaticContentProcessMock;

    /**
     * @var WritableDirectories|Mock
     */
    private $backupWritableDirectoriesProcessMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var LoggerPool|Mock
     */
    private $loggerPoolMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->backupStaticContentProcessMock = $this->createMock(StaticContent::class);
        $this->backupWritableDirectoriesProcessMock = $this->createMock(WritableDirectories::class);
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->setMethods(['setHandlers'])
            ->getMockForAbstractClass();
        $this->loggerPoolMock = $this->createMock(LoggerPool::class);

        $this->process = new BackupData(
            $this->backupStaticContentProcessMock,
            $this->backupWritableDirectoriesProcessMock,
            $this->loggerMock,
            $this->loggerPoolMock
        );
    }

    public function testExecute()
    {
        $this->backupStaticContentProcessMock->expects($this->once())
            ->method('execute');
        $this->backupWritableDirectoriesProcessMock->expects($this->once())
            ->method('execute');
        $this->loggerPoolMock->expects($this->once())
            ->method('getHandlers')
            ->willReturn(['handler1', 'handler2']);
        $this->loggerMock->expects($this->exactly(2))
            ->method('setHandlers')
            ->withConsecutive(
                [[]],
                [['handler1', 'handler2']]
            );

        $this->process->execute();
    }
}
