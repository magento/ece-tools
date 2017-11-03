<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\ConfigDump;

use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Process\ConfigDump\Export;
use Magento\MagentoCloud\Shell\ShellInterface;
use PHPUnit\Framework\TestCase;

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
     * @var ShellInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shellMock;

    /**
     * @var File|\PHPUnit_Framework_MockObject_MockObject
     */
    private $fileMock;

    /**
     * @var FileList|\PHPUnit_Framework_MockObject_MockObject
     */
    private $fileListMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->shellMock = $this->getMockForAbstractClass(ShellInterface::class);
        $this->fileMock = $this->createMock(File::class);
        $this->fileListMock = $this->createMock(FileList::class);

        $this->process = new Export(
            $this->shellMock,
            $this->fileMock,
            $this->fileListMock
        );
    }

    public function testProcess()
    {
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->withConsecutive(
                ['php ./bin/magento app:config:dump']
            );
        $this->fileListMock->expects($this->once())
            ->method('getConfig')
            ->willReturn('magento_root/app/etc/config.php');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with('magento_root/app/etc/config.php')
            ->willReturn(true);

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
        $this->fileListMock->expects($this->once())
            ->method('getConfig')
            ->willReturn('magento_root/app/etc/config.php');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with('magento_root/app/etc/config.php')
            ->willReturn(false);

        $this->process->execute();
    }
}
