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
     * @var File|Mock
     */
    private $fileMock;

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

    protected function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->fileMock = $this->createMock(File::class);
        $this->buildDirCopierMock = $this->createMock(BuildDirCopier::class);
        $this->recoverableDirectoryListMock = $this->getMockBuilder(RecoverableDirectoryList::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->directoryListMock = $this->createMock(DirectoryList::class);

        $this->process = new RestoreWritableDirectories(
            $this->loggerMock,
            $this->fileMock,
            $this->buildDirCopierMock,
            $this->recoverableDirectoryListMock,
            $this->directoryListMock
        );
    }

    public function testExecute()
    {
        $this->directoryListMock->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with('magento_root/' . Environment::REGENERATE_FLAG)
            ->willReturn(true);
        $this->fileMock->expects($this->once())
            ->method('deleteFile')
            ->with('magento_root/' . Environment::REGENERATE_FLAG);
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
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Recoverable directories were copied back.'],
                ['Removing var/.regenerate flag']
            );

        $this->process->execute();
    }

    public function testExecuteFlagNotExists()
    {
        $this->directoryListMock->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with('magento_root/' . Environment::REGENERATE_FLAG)
            ->willReturn(false);
        $this->fileMock->expects($this->never())
            ->method('deleteFile');
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

        $this->process->execute();
    }
}
