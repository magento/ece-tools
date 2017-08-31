<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\PreDeploy;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Process\Deploy\PreDeploy\CleanFileCache;
use Magento\MagentoCloud\Shell\ShellInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

class CleanFileCacheTest extends TestCase
{
    /**
     * @var ShellInterface|Mock
     */
    private $shellMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var File|Mock
     */
    private $fileMock;

    /**
     * @var DirectoryList|Mock
     */
    private $directoryListMock;

    /**
     * @var CleanFileCache
     */
    private $process;

    protected function setUp()
    {
        $this->shellMock = $this->getMockBuilder(ShellInterface::class)
            ->getMockForAbstractClass();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->fileMock = $this->createMock(File::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);

        $this->process = new CleanFileCache(
            $this->loggerMock,
            $this->shellMock,
            $this->directoryListMock,
            $this->fileMock
        );
    }

    public function testExecute()
    {
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn('/path/to/root');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with('/path/to/root/var/cache')
            ->willReturn(true);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Clearing var/cache directory');
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('rm -rf /path/to/root/var/cache');

        $this->process->execute();
    }

    public function testExecuteNoCacheDir()
    {
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn('/path/to/root');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with('/path/to/root/var/cache')
            ->willReturn(false);
        $this->loggerMock->expects($this->never())
            ->method('info');
        $this->shellMock->expects($this->never())
            ->method('execute');

        $this->process->execute();
    }
}
