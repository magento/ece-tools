<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\PreDeploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\FlagFile\Flag\StaticContentDeployInBuild;
use Magento\MagentoCloud\Process\Deploy\PreDeploy\CleanStaticContent;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FlagFile\Manager as FlagFileManager;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

class CleanStaticContentTest extends TestCase
{
    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @var DirectoryList|Mock
     */
    private $directoryListMock;

    /**
     * @var File|Mock
     */
    private $fileMock;

    /**
     * @var FlagFileManager|Mock
     */
    private $flagFileManagerMock;

    /**
     * @var CleanStaticContent
     */
    private $process;



    protected function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->environmentMock = $this->createMock(Environment::class);
        $this->fileMock = $this->createMock(File::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->flagFileManagerMock = $this->createMock(FlagFileManager::class);

        $this->process = new CleanStaticContent(
            $this->loggerMock,
            $this->environmentMock,
            $this->fileMock,
            $this->directoryListMock,
            $this->flagFileManagerMock
        );
    }

    public function testExecute()
    {
        $this->flagFileManagerMock->expects($this->once())
            ->method('exists')
            ->with(StaticContentDeployInBuild::KEY)
            ->willReturn(true);
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->expects($this->once())
            ->method('backgroundClearDirectory')
            ->with('magento_root/pub/static');
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Static content deployment was performed during build hook, cleaning old content.'],
                ['Clearing pub/static']
            );

        $this->process->execute();
    }

    public function testExecuteWithoutDeployInBuild()
    {
        $this->flagFileManagerMock->expects($this->once())
            ->method('exists')
            ->with(StaticContentDeployInBuild::KEY)
            ->willReturn(false);
        $this->directoryListMock->expects($this->never())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->expects($this->never())
            ->method('backgroundClearDirectory')
            ->with('magento_root/pub/static');

        $this->process->execute();
    }
}
