<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Build\BackupData;

use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Process\Build\BackupData\StaticContent;
use Magento\MagentoCloud\Filesystem\FileSystemException;

/**
 * @inheritdoc
 */
class StaticContentTest extends TestCase
{
    /**
     * @var StaticContent
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
     * @var DirectoryList|Mock
     */
    private $directoryListMock;

    /**
     * @var FlagManager|Mock
     */
    private $flagManagerMock;

    /**
     * @var string
     */
    private $rootInitDir = 'magento_root/init';

    /**
     * @var string
     */
    private $initPubStaticPath = 'magento_root/init/pub/static';

    /**
     * @var string
     */
    private $originalPubStaticPath = 'magento_root/pub/static';

    protected function setUp()
    {
        $this->fileMock = $this->createMock(File::class);
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->setMethods(['info'])
            ->getMockForAbstractClass();
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->flagManagerMock = $this->createMock(FlagManager::class);

        $this->flagManagerMock->expects($this->once())
            ->method('delete')
            ->with(FlagManager::FLAG_REGENERATE);

        $this->process = new StaticContent(
            $this->fileMock,
            $this->loggerMock,
            $this->directoryListMock,
            $this->flagManagerMock
        );
    }

    public function testExecuteFlagSCDInBuildExistsAndInitPubStaticExists()
    {
        $this->flagManagerMock->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD)
            ->willReturn(true);
        $this->directoryListMock->expects($this->exactly(2))
            ->method('getPath')
            ->willReturnMap([
                [DirectoryList::DIR_STATIC, false, $this->originalPubStaticPath],
                [DirectoryList::DIR_INIT, false, $this->rootInitDir]
            ]);
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->withConsecutive(
                [$this->initPubStaticPath]
            )
            ->willReturn(true);
        $this->loggerMock->expects($this->exactly(3))
            ->method('info')
            ->withConsecutive(
                ['Clear ./init/pub/static'],
                ['Moving static content to init directory'],
                ['Recreating pub/static directory']
            );
        $this->fileMock->expects($this->once())
            ->method('backgroundClearDirectory')
            ->with($this->initPubStaticPath);
        $this->fileMock->expects($this->once())
            ->method('createDirectory')
            ->with($this->originalPubStaticPath);
        $this->fileMock->expects($this->once())
            ->method('rename')
            ->with($this->originalPubStaticPath, $this->initPubStaticPath);

        $this->fileMock->expects($this->never())
            ->method('copyDirectory');

        $this->process->execute();
    }

    public function testExecuteFlagSCDInBuildExistsAndInitPubStaticDoesNotExist()
    {
        $this->flagManagerMock->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD)
            ->willReturn(true);
        $this->directoryListMock->expects($this->exactly(2))
            ->method('getPath')
            ->willReturnMap([
                [DirectoryList::DIR_STATIC, false, $this->originalPubStaticPath],
                [DirectoryList::DIR_INIT, false, $this->rootInitDir]
            ]);
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->withConsecutive(
                [$this->initPubStaticPath]
            )
            ->willReturnOnConsecutiveCalls(false, true);
        $this->loggerMock->expects($this->exactly(3))
            ->method('info')
            ->withConsecutive(
                ['Create ./init/pub/static'],
                ['Moving static content to init directory'],
                ['Recreating pub/static directory']
            );
        $this->fileMock->expects($this->never())
            ->method('backgroundClearDirectory')
            ->with($this->initPubStaticPath);
        $this->fileMock->expects($this->exactly(2))
            ->method('createDirectory')
            ->withConsecutive(
                [$this->initPubStaticPath],
                [$this->originalPubStaticPath]
            );
        $this->fileMock->expects($this->once())
            ->method('rename')
            ->with($this->originalPubStaticPath, $this->initPubStaticPath);
        $this->fileMock->expects($this->never())
            ->method('copyDirectory');

        $this->process->execute();
    }

    public function testExecuteSCDInAndInitPubStaticDoesNotExistAndRecreatePubStatic()
    {
        $this->flagManagerMock->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD)
            ->willReturn(true);
        $this->directoryListMock->expects($this->exactly(2))
            ->method('getPath')
            ->willReturnMap([
                [DirectoryList::DIR_STATIC, false, $this->originalPubStaticPath],
                [DirectoryList::DIR_INIT, false, $this->rootInitDir]
            ]);
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->withConsecutive(
                [$this->initPubStaticPath]
            )
            ->willReturnOnConsecutiveCalls(false, false);
        $this->loggerMock->expects($this->exactly(3))
            ->method('info')
            ->withConsecutive(
                ['Create ./init/pub/static'],
                ['Moving static content to init directory'],
                ['Recreating pub/static directory']
            );
        $this->fileMock->expects($this->never())
            ->method('backgroundClearDirectory')
            ->with($this->initPubStaticPath);
        $this->fileMock->expects($this->exactly(2))
            ->method('createDirectory')
            ->withConsecutive(
                [$this->initPubStaticPath],
                [$this->originalPubStaticPath]
            )
            ->willReturn(true);
        $this->fileMock->expects($this->once())
            ->method('rename')
            ->with($this->originalPubStaticPath, $this->initPubStaticPath);
        $this->fileMock->expects($this->never())
            ->method('copyDirectory');

        $this->process->execute();
    }

    public function testExecuteFlagSCDInBuildDoesNotExist()
    {
        $this->flagManagerMock->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD)
            ->willReturn(false);

        $this->directoryListMock->expects($this->never())
            ->method('getPath');

        $this->fileMock->expects($this->never())
            ->method('isExists');

        $this->fileMock->expects($this->never())
            ->method('backgroundClearDirectory');

        $this->fileMock->expects($this->never())
            ->method('createDirectory');

        $this->fileMock->expects($this->never())
            ->method('copyDirectory');

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('SCD not performed during build');

        $this->process->execute();
    }

    public function testRenameFails()
    {
        $this->flagManagerMock->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD)
            ->willReturn(true);
        $this->directoryListMock->expects($this->exactly(2))
            ->method('getPath')
            ->willReturnMap([
                [DirectoryList::DIR_STATIC, false, $this->originalPubStaticPath],
                [DirectoryList::DIR_INIT, false, $this->rootInitDir]
            ]);
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->withConsecutive(
                [$this->initPubStaticPath]
            )
            ->willReturn(false, true);
        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with('Can\'t move static content. Copying static content to init directory');
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Create ./init/pub/static'],
                ['Moving static content to init directory']
            );
        $this->fileMock->expects($this->never())
            ->method('backgroundClearDirectory')
            ->with($this->initPubStaticPath);
        $this->fileMock->expects($this->once())
            ->method('createDirectory')
            ->with($this->initPubStaticPath);
        $this->fileMock->expects($this->once())
            ->method('rename')
            ->with($this->originalPubStaticPath, $this->initPubStaticPath)
            ->willThrowException(new FileSystemException('Some error'));
        $this->fileMock->expects($this->once())
            ->method('copyDirectory')
            ->with($this->originalPubStaticPath, $this->initPubStaticPath);

        $this->process->execute();
    }
}
