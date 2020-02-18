<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Patch;

use Composer\Package\RootPackageInterface;
use Magento\MagentoCloud\Config\GlobalSection;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Patch\Manager;
use Magento\MagentoCloud\Shell\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
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
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var ShellInterface|MockObject
     */
    private $shellMock;

    /**
     * @var RootPackageInterface|MockObject
     */
    private $composerPackageMock;

    /**
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @var DirectoryList|MockObject
     */
    private $directoryListMock;

    /**
     * @var GlobalSection|MockObject
     */
    private $globalSectionMock;

    /**
     * @var FileList|MockObject
     */
    private $fileListMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->shellMock = $this->getMockForAbstractClass(ShellInterface::class);
        $this->composerPackageMock = $this->getMockForAbstractClass(RootPackageInterface::class);
        $this->fileMock = $this->createMock(File::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->globalSectionMock = $this->createMock(GlobalSection::class);
        $this->fileListMock = $this->createMock(FileList::class);

        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileListMock->expects($this->once())
            ->method('getFrontStaticDist')
            ->willReturn('dist/pub/front-static.php');
        $this->fileMock->expects($this->once())
            ->method('copy')
            ->with(
                'dist/pub/front-static.php',
                'magento_root/pub/front-static.php'
            );
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('File "front-static.php" was copied');

        $this->manager = new Manager(
            $this->loggerMock,
            $this->shellMock,
            $this->fileMock,
            $this->directoryListMock,
            $this->globalSectionMock,
            $this->fileListMock
        );
    }

    public function testApply()
    {
        $this->globalSectionMock->expects($this->once())
            ->method('get')
            ->with(GlobalSection::VAR_DEPLOYED_MAGENTO_VERSION_FROM_GIT)
            ->willReturn(false);
        $this->loggerMock->expects($this->exactly(2))
            ->method('notice')
            ->withConsecutive(
                ['Applying patches'],
                ['End of applying patches']
            );
        $processMock = $this->getMockForAbstractClass(ProcessInterface::class);
        $processMock->method('getOutput')
            ->willReturn('Some patch applied');
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('php ./vendor/bin/ece-patches apply')
            ->willReturn($processMock);

        $this->manager->apply();
    }

    public function testApplyDeployedFromGitAndNoCopy()
    {
        $this->globalSectionMock->expects($this->once())
            ->method('get')
            ->with(GlobalSection::VAR_DEPLOYED_MAGENTO_VERSION_FROM_GIT)
            ->willReturn(true);
        $this->loggerMock->method('notice')
            ->withConsecutive(
                ['Applying patches'],
                ['End of applying patches']
            );
        $processMock = $this->getMockForAbstractClass(ProcessInterface::class);
        $processMock->method('getOutput')
            ->willReturn('Some patch applied');
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('php ./vendor/bin/ece-patches apply --git-installation 1')
            ->willReturn($processMock);

        $this->manager->apply();
    }

    public function testApplyWithException()
    {
        $this->globalSectionMock->expects($this->once())
            ->method('get')
            ->with(GlobalSection::VAR_DEPLOYED_MAGENTO_VERSION_FROM_GIT)
            ->willReturn(false);
        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with('Applying patches');
        $processMock = $this->getMockForAbstractClass(ProcessInterface::class);
        $processMock->method('getOutput')
            ->willReturn('Some patch applied');
        $exception = new ShellException('Some Error');
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('php ./vendor/bin/ece-patches apply')
            ->willThrowException($exception);
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with('Some Error');
        $this->expectExceptionObject($exception);

        $this->manager->apply();
    }
}
