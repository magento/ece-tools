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
use Magento\MagentoCloud\Patch\Manager;
use Magento\MagentoCloud\Shell\ProcessInterface;
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

        $this->directoryListMock->method('getMagentoRoot')
            ->willReturn('magento_root');

        $this->manager = new Manager(
            $this->loggerMock,
            $this->shellMock,
            $this->fileMock,
            $this->directoryListMock,
            $this->globalSectionMock
        );
    }

    public function testApply()
    {
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with('magento_root/pub/static.php')
            ->willReturn(true);
        $this->fileMock->expects($this->once())
            ->method('copy')
            ->with(
                'magento_root/pub/static.php',
                'magento_root/pub/front-static.php'
            );
        $this->globalSectionMock->expects($this->once())
            ->method('get')
            ->with(GlobalSection::VAR_DEPLOYED_MAGENTO_VERSION_FROM_GIT)
            ->willReturn(false);

        $processMock = $this->getMockForAbstractClass(ProcessInterface::class);
        $processMock->method('getOutput')
            ->willReturn('Some patch applied');

        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('php ./vendor/bin/ece-patches apply')
            ->willReturn($processMock);
        $this->loggerMock->method('info')
            ->withConsecutive(
                ['File static.php was copied'],
                ["Patching log: \nSome patch applied"]
            );
        $this->loggerMock->method('notice')
            ->withConsecutive(
                ['Applying patches'],
                ['End of applying patches']
            );

        $this->manager->apply();
    }

    public function testApplyDeployedFromGitAndNoCopy()
    {
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with('magento_root/pub/static.php')
            ->willReturn(false);
        $this->globalSectionMock->expects($this->once())
            ->method('get')
            ->with(GlobalSection::VAR_DEPLOYED_MAGENTO_VERSION_FROM_GIT)
            ->willReturn(true);

        $processMock = $this->getMockForAbstractClass(ProcessInterface::class);
        $processMock->method('getOutput')
            ->willReturn('Some patch applied');

        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('php ./vendor/bin/ece-patches apply --git-installation 1')
            ->willReturn($processMock);
        $this->loggerMock->method('info')
            ->withConsecutive(
                ["Patching log: \nSome patch applied"]
            );
        $this->loggerMock->method('notice')
            ->withConsecutive(
                ['File static.php was not found'],
                ['Applying patches'],
                ['End of applying patches']
            );

        $this->manager->apply();
    }
}
