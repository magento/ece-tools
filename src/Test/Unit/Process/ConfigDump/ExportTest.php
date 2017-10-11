<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\ConfigDump;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Process\ConfigDump\Export;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class ExportTest extends TestCase
{
    /**
     * @var Export
     */
    private $process;

    /**
     * @var ProcessInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $processMock;

    /**
     * @var ShellInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shellMock;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerMock;

    /**
     * @var File|\PHPUnit_Framework_MockObject_MockObject
     */
    private $fileMock;

    /**
     * @var DirectoryList|\PHPUnit_Framework_MockObject_MockObject
     */
    private $directoryListMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->processMock = $this->getMockForAbstractClass(ProcessInterface::class);
        $this->shellMock = $this->getMockForAbstractClass(ShellInterface::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->fileMock = $this->createMock(File::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);

        $this->process = new Export(
            $this->processMock,
            $this->shellMock,
            $this->loggerMock,
            $this->fileMock,
            $this->directoryListMock
        );
    }

    public function testProcess()
    {
        $this->shellMock->method('execute')
            ->withConsecutive(
                ['php ./bin/magento app:config:dump'],
                ['php ./bin/magento app:config:import -n']
            );
        $this->directoryListMock->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->method('isExists')
            ->with('magento_root/app/etc/config.php')
            ->willReturn(true);
        $this->processMock->method('execute');

        $this->process->execute();
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Config file was not found.
     */
    public function testExecuteNoConfigFile()
    {
        $this->shellMock->method('execute')
            ->withConsecutive(
                ['php ./bin/magento app:config:dump']
            );
        $this->directoryListMock->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->method('isExists')
            ->with('magento_root/app/etc/config.php')
            ->willReturn(false);
        $this->processMock->expects($this->never())
            ->method('execute');

        $this->process->execute();
    }
}
