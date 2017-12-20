<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\PreDeploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Process\Deploy\PreDeploy\CleanStaticContent;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FlagFilePool;
use Magento\MagentoCloud\Filesystem\FlagFileInterface;
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
     * @var FlagFilePool|Mock
     */
    private $flagFilePoolMock;

    /**
     * @var FlagFileInterface|Mock
     */
    private $flagMock;

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
        $this->flagFilePoolMock = $this->createMock(FlagFilePool::class);
        $this->flagMock = $this->getMockBuilder(FlagFileInterface::class)
            ->getMockForAbstractClass();

        $this->process = new CleanStaticContent(
            $this->loggerMock,
            $this->environmentMock,
            $this->fileMock,
            $this->directoryListMock,
            $this->flagFilePoolMock
        );
    }

    public function testExecute()
    {
        $this->flagFilePoolMock->expects($this->once())
            ->method('getFlag')
            ->with('scd_in_build')
            ->willReturn($this->flagMock);
        $this->flagMock->expects($this->once())
            ->method('exists')
            ->willReturn(true);
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->expects($this->exactly(2))
            ->method('backgroundClearDirectory')
            ->withConsecutive(
                ['magento_root/pub/static'],
                ['magento_root/var/view_preprocessed']
            );
        $this->loggerMock->expects($this->exactly(3))
            ->method('info')
            ->withConsecutive(
                ['Static content deployment was performed during build hook, cleaning old content.'],
                ['Clearing pub/static'],
                ['Clearing var/view_preprocessed']
            );

        $this->process->execute();
    }

    public function testExecuteWithoutDeployInBuild()
    {
        $this->flagFilePoolMock->expects($this->once())
            ->method('getFlag')
            ->with('scd_in_build')
            ->willReturn($this->flagMock);
        $this->flagMock->expects($this->once())
            ->method('exists')
            ->willReturn(false);
        $this->directoryListMock->expects($this->never())
            ->method('getMagentoRoot');
        $this->fileMock->expects($this->never())
            ->method('backgroundClearDirectory');

        $this->process->execute();
    }
}
