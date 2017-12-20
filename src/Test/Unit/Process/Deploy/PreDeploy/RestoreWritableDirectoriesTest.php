<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\PreDeploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\RecoverableDirectoryList;
use Magento\MagentoCloud\Filesystem\FlagFilePool;
use Magento\MagentoCloud\Filesystem\FlagFileInterface;
use Magento\MagentoCloud\Process\Deploy\PreDeploy\RestoreWritableDirectories;
use Magento\MagentoCloud\Util\BuildDirCopier;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

class RestoreWritableDirectoriesTest extends TestCase
{
    /**
     * @var RestoreWritableDirectories
     */
    private $process;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var BuildDirCopier|Mock
     */
    private $buildDirCopierMock;

    /**
     * @var DirectoryList|Mock
     */
    private $directoryListMock;

    /**
     * @var RecoverableDirectoryList|Mock
     */
    private $recoverableDirectoryListMock;

    /**
     * @var FlagFilePool|Mock
     */
    private $flagFilePoolMock;

    /**
     * @var FlagFileInterface|Mock
     */
    private $flagMock;

    protected function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->buildDirCopierMock = $this->createMock(BuildDirCopier::class);
        $this->recoverableDirectoryListMock = $this->getMockBuilder(RecoverableDirectoryList::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->flagFilePoolMock = $this->createMock(FlagFilePool::class);
        $this->flagMock = $this->getMockBuilder(FlagFileInterface::class)
            ->getMockForAbstractClass();

        $this->process = new RestoreWritableDirectories(
            $this->loggerMock,
            $this->buildDirCopierMock,
            $this->recoverableDirectoryListMock,
            $this->directoryListMock,
            $this->flagFilePoolMock
        );
    }

    public function testExecute()
    {
        $this->recoverableDirectoryListMock->expects($this->once())
            ->method('getList')
            ->willReturn([
                ['directory' => 'app/etc', 'strategy' => 'copy'],
                ['directory' => 'pub/media', 'strategy' => 'copy']
            ]);
        $this->buildDirCopierMock->expects($this->exactly(2))
            ->method('copy')
            ->withConsecutive(
                ['app/etc', 'copy'],
                ['pub/media', 'copy']
            );
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Recoverable directories were copied back.');

        $this->flagFilePoolMock->expects($this->once())
            ->method('getFlag')
            ->with('regenerate')
            ->willReturn($this->flagMock);
        $this->flagMock->expects($this->once())
            ->method('delete');

        $this->process->execute();
    }
}
