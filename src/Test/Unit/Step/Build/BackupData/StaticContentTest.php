<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Build\BackupData;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Step\StepException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Step\Build\BackupData\StaticContent;
use Magento\MagentoCloud\Filesystem\FileSystemException;

/**
 * @inheritdoc
 */
class StaticContentTest extends TestCase
{
    /**
     * @var StaticContent
     */
    private $step;

    /**
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var DirectoryList|MockObject
     */
    private $directoryListMock;

    /**
     * @var FlagManager|MockObject
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

    /**
     * @inheritDoc
     */
    protected function setUp(): void
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

        $this->step = new StaticContent(
            $this->fileMock,
            $this->loggerMock,
            $this->directoryListMock,
            $this->flagManagerMock
        );
    }

    /**
     * @throws StepException
     */
    public function testExecuteFlagSCDInBuildExistsAndInitPubStaticExists(): void
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
            )->willReturn(true);
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

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteFlagSCDInBuildExistsAndInitPubStaticDoesNotExist(): void
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

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteSCDInAndInitPubStaticDoesNotExistAndRecreatePubStatic(): void
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
            )->willReturnOnConsecutiveCalls(false, false);
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
            )->willReturn(true);
        $this->fileMock->expects($this->once())
            ->method('rename')
            ->with($this->originalPubStaticPath, $this->initPubStaticPath);
        $this->fileMock->expects($this->never())
            ->method('copyDirectory');

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteFlagSCDInBuildDoesNotExist(): void
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

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testRenameFails(): void
    {
        $this->prepareCopying();
        $this->fileMock->expects($this->once())
            ->method('copyDirectory')
            ->with($this->originalPubStaticPath, $this->initPubStaticPath);

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testCopyingWithException(): void
    {
        $this->prepareCopying();
        $this->fileMock->expects($this->once())
            ->method('copyDirectory')
            ->with($this->originalPubStaticPath, $this->initPubStaticPath)
            ->willThrowException(new FileSystemException('some error'));

        $this->expectException(StepException::class);
        $this->expectExceptionMessage('some error');
        $this->expectExceptionCode(Error::BUILD_SCD_COPYING_FAILED);

        $this->step->execute();
    }

    public function prepareCopying(): void
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
            )->willReturn(false, true);
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
    }

    /**
     * @throws StepException
     */
    public function testClearingDirectoryWithFileSystemException()
    {
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('some error');
        $this->expectExceptionCode(Error::BUILD_CLEAN_INIT_PUB_STATIC_FAILED);

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
            ->willReturn(true);
        $this->fileMock->expects($this->once())
            ->method('backgroundClearDirectory')
            ->with($this->initPubStaticPath)
            ->willThrowException(new FileSystemException('some error'));

        $this->step->execute();
    }
}
