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
use Magento\MagentoCloud\Patch\ApplierFactory;
use Magento\MagentoCloud\Patch\ApplierInterface;
use Magento\MagentoCloud\Patch\ConstraintTester;
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
     * @var ApplierInterface|Mock
     */
    private $applierMock;

    /**
     * @var ConstraintTester|Mock
     */
    private $constraintTesterMock;

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
        $this->applierMock = $this->createMock(ApplierInterface::class);
        $applierFactoryMock = $this->createMock(ApplierFactory::class);
        $applierFactoryMock->expects($this->any())->method('create')->willReturn($this->applierMock);
        $this->constraintTesterMock = $this->createMock(ConstraintTester::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->composerPackageMock = $this->getMockForAbstractClass(RootPackageInterface::class);
        $this->fileMock = $this->createMock(File::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->fileListMock = $this->createMock(FileList::class);

        $this->manager = new Manager(
            $applierFactoryMock,
            $this->constraintTesterMock,
            $this->loggerMock,
            $this->fileMock,
            $this->fileListMock,
            $this->directoryListMock
        );
    }

    public function testExecuteCopyStaticFiles()
    {
        $this->fileMock->expects($this->at(0))
            ->method('isExists')
            ->with('/pub/front-static.php')
            ->willReturn(false);
        $this->fileMock->expects($this->at(1))
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
        $this->fileMock->expects($this->any())
            ->method('isExists')
            ->willReturn(false);
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
        $this->applierMock->expects($this->once())
            ->method('applyPatches')
            ->with([
                ['path' => 'patchPath1', 'name' => 'patchName1'],
                ['path' => 'patchPath2', 'name' => 'patchName2'],
                ['path' => 'patchPath3', 'name' => 'patchName3'],
                ['path' => 'patchPath4', 'name' => 'patchName4'],
            ]);
        $this->constraintTesterMock->expects($this->any())
            ->method('testConstraint')
            ->willReturnArgument(0);

        $this->manager->applyAll();
    }

    public function testExecuteApplyHotFixes()
    {
        $this->fileMock->expects($this->any())
            ->method('isExists')
            ->willReturn(false);
        $this->directoryListMock->expects($this->any())
            ->method('getPatches')
            ->willReturn(__DIR__ . '/_files');
        $this->fileMock->expects($this->once())
            ->method('isDirectory')
            ->willReturn(true);
        $this->applierMock->expects($this->once())
            ->method('applyPatches')
            ->with([
                ['path' => __DIR__ . '/_files/' . Manager::HOTFIXES_DIR . '/patch1.patch'],
                ['path' => __DIR__ . '/_files/' . Manager::HOTFIXES_DIR . '/patch2.patch'],
            ]);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->withConsecutive(
                ['Applying hot-fixes.']
            );
        $this->constraintTesterMock->expects($this->any())
            ->method('testConstraint')
            ->willReturnArgument(0);

        $this->manager->applyAll();
    }

    public function testUnapplyAll()
    {
        $this->applierMock->expects($this->once())
            ->method('unapplyAllPatches');
        $this->manager->unapplyAll();
    }

    public function testShowApplied()
    {
        $this->applierMock->expects($this->once())
            ->method('showAppliedPatches');
        $this->manager->showApplied();
    }
}
