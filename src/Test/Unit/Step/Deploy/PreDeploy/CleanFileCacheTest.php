<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy\PreDeploy;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Step\Deploy\PreDeploy\CleanFileCache;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class CleanFileCacheTest extends TestCase
{
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
    private $step;

    protected function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->fileMock = $this->createMock(File::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);

        $this->step = new CleanFileCache(
            $this->loggerMock,
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
        $this->fileMock->expects($this->once())
            ->method('deleteDirectory')
            ->with('/path/to/root/var/cache')
            ->willReturn(true);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Clearing var/cache directory');

        $this->step->execute();
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
        $this->fileMock->expects($this->never())
            ->method('deleteDirectory');
        $this->loggerMock->expects($this->never())
            ->method('info');

        $this->step->execute();
    }
}
