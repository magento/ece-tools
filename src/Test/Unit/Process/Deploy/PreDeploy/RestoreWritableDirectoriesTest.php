<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\PreDeploy;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\FlagFile\Flag\Regenerate;
use Magento\MagentoCloud\Filesystem\FlagFile\Manager;
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
     * @var Manager|Mock
     */
    private $flagFileManagerMock;

    protected function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->buildDirCopierMock = $this->createMock(BuildDirCopier::class);
        $this->recoverableDirectoryListMock = $this->getMockBuilder(RecoverableDirectoryList::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->flagFileManagerMock = $this->createMock(Manager::class);

        $this->process = new RestoreWritableDirectories(
            $this->loggerMock,
            $this->buildDirCopierMock,
            $this->recoverableDirectoryListMock,
            $this->directoryListMock,
            $this->flagFileManagerMock
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
        $this->flagFileManagerMock->expects($this->once())
            ->method('delete')
            ->with(Regenerate::KEY);

        $this->process->execute();
    }
}
