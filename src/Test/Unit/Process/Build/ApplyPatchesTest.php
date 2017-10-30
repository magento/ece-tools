<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Build;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Process\Build\ApplyPatches;
use Magento\MagentoCloud\Shell\ShellInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class ApplyPatchesTest extends TestCase
{
    /**
     * @var ApplyPatches
     */
    private $process;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var ShellInterface|Mock
     */
    private $shellMock;

    /**
     * @var File|Mock
     */
    private $fileMock;

    /**
     * @var DirectoryList|Mock
     */
    private $directoryListMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->shellMock = $this->getMockBuilder(ShellInterface::class)
            ->getMockForAbstractClass();
        $this->fileMock = $this->getMockBuilder(File::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->directoryListMock = $this->createMock(DirectoryList::class);

        $this->process = new ApplyPatches(
            $this->shellMock,
            $this->loggerMock,
            $this->fileMock,
            $this->directoryListMock
        );

        parent::setUp();
    }

    public function testExecute()
    {
        $this->directoryListMock->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Applying patches.');
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('php magento_root/vendor/bin/m2-apply-patches');
        $this->fileMock->method('isExists')
            ->with('magento_root/vendor/bin/m2-apply-patches')
            ->willReturn(true);

        $this->process->execute();
    }

    public function testExecuteWithoutPatches()
    {
        $this->directoryListMock->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Applying patches.');
        $this->fileMock->method('isExists')
            ->with('magento_root/vendor/bin/m2-apply-patches')
            ->willReturn(false);
        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with('Package with patches was not found.');
        $this->shellMock->expects($this->never())
            ->method('execute');

        $this->process->execute();
    }
}
