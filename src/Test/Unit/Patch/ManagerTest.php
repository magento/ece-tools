<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Patch;

use Composer\Package\RootPackageInterface;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Patch\Applier;
use Magento\MagentoCloud\Patch\Manager;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class ManagerTest extends TestCase
{
    /**
     * @var Manager
     */
    private $manager;

    /**
     * @var Applier|Mock
     */
    private $applierMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var RootPackageInterface|Mock
     */
    private $composerPackageMock;

    /**
     * @var File|Mock
     */
    private $fileMock;

    /**
     * @var FileList|Mock
     */
    private $fileListMock;

    /**
     * @var DirectoryList|Mock
     */
    private $directoryListMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->applierMock = $this->createMock(Applier::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->composerPackageMock = $this->getMockForAbstractClass(RootPackageInterface::class);
        $this->fileMock = $this->createMock(File::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->fileListMock = $this->createMock(FileList::class);

        $this->manager = new Manager(
            $this->applierMock,
            $this->loggerMock,
            $this->fileMock,
            $this->fileListMock,
            $this->directoryListMock
        );
    }

    public function testExecuteCopyStaticFiles()
    {
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with('/pub/static.php')
            ->willReturn(true);
        $this->fileMock->expects($this->once())
            ->method('copy')
            ->with('/pub/static.php', '/pub/front-static.php')
            ->willReturn(true);

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->withConsecutive(
                ['File static.php was copied.']
            );

        $this->manager->applyAll();
    }

    public function testExecuteApplyComposerPatches()
    {
        $this->fileMock->expects($this->once())
            ->method('fileGetContents')
            ->willReturn(json_encode(
                [
                    'package1' => [
                        'patchName1' => [
                            '100' => 'patchPath1',
                        ],
                    ],
                    'package2' => [
                        'patchName2' => [
                            '101.*' => 'patchPath2',
                        ],
                        'patchName3' => [
                            '102.*' => 'patchPath3',
                        ],
                    ],
                    'package3' => [
                        'patchName4' => 'patchPath4',
                    ],
                ]
            ));
        $this->applierMock->expects($this->exactly(4))
            ->method('apply')
            ->withConsecutive(
                ['patchPath1', 'patchName1', 'package1', '100'],
                ['patchPath2', 'patchName2', 'package2', '101.*'],
                ['patchPath3', 'patchName3', 'package2', '102.*'],
                ['patchPath4', 'patchName4', 'package3', '*']
            );

        $this->manager->applyAll();
    }

    public function testExecuteApplyHotFixes()
    {
        $this->directoryListMock->expects($this->any())
            ->method('getMagentoRoot')
            ->willReturn(__DIR__ . '/_files');
        $this->fileMock->expects($this->once())
            ->method('isDirectory')
            ->willReturn(true);
        $this->applierMock->expects($this->exactly(2))
            ->method('apply')
            ->withConsecutive(
                [__DIR__ . '/_files/' . Manager::HOTFIXES_DIR . '/patch1.patch'],
                [__DIR__ . '/_files/' . Manager::HOTFIXES_DIR . '/patch2.patch']
            );
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->withConsecutive(
                ['Applying hot-fixes.']
            );

        $this->manager->applyAll();
    }
}
