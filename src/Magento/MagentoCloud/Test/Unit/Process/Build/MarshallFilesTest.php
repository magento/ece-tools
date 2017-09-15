<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Build;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Process\Build\MarshallFiles;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class MarshallFilesTest extends TestCase
{
    /**
     * @var MarshallFiles
     */
    private $process;

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
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->fileMock = $this->getMockBuilder(File::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->directoryListMock = $this->getMockBuilder(DirectoryList::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->directoryListMock->method('getMagentoRoot')
            ->willReturn('magento_root');

        $this->process = new MarshallFiles(
            $this->loggerMock,
            $this->fileMock,
            $this->directoryListMock
        );
    }

    public function testExecute()
    {
        $this->fileMock->expects($this->exactly(2))
            ->method('clearDirectory')
            ->withConsecutive(
                ['magento_root/generated/code/'],
                ['magento_root/var/metadata/']
            )
            ->willReturn(true);
        $this->fileMock->expects($this->once())
            ->method('deleteDirectory')
            ->with('magento_root/var/cache/')
            ->willReturn(true);
        $this->fileMock->expects($this->exactly(2))
            ->method('copy')
            ->withConsecutive(
                ['magento_root/app/etc/di.xml', 'magento_root/app/di.xml'],
                ['magento_root/app/etc/enterprise/di.xml', 'magento_root/app/enterprise/di.xml']
            );
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with('magento_root/app/enterprise')
            ->willReturn(true);

        $this->process->execute();
    }

    public function testExecuteNoEnterpriseFolder()
    {
        $this->fileMock->expects($this->exactly(2))
            ->method('clearDirectory')
            ->withConsecutive(
                ['magento_root/generated/code/'],
                ['magento_root/var/metadata/']
            )
            ->willReturn(true);
        $this->fileMock->expects($this->once())
            ->method('deleteDirectory')
            ->with('magento_root/var/cache/')
            ->willReturn(true);
        $this->fileMock->expects($this->exactly(2))
            ->method('copy')
            ->withConsecutive(
                ['magento_root/app/etc/di.xml', 'magento_root/app/di.xml'],
                ['magento_root/app/etc/enterprise/di.xml', 'magento_root/app/enterprise/di.xml']
            );
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with('magento_root/app/enterprise')
            ->willReturn(false);
        $this->fileMock->expects($this->once())
            ->method('createDirectory')
            ->with('magento_root/app/enterprise', 0777);

        $this->process->execute();
    }

    public function testExecuteWithException()
    {
        $this->fileMock->expects($this->exactly(2))
            ->method('clearDirectory')
            ->withConsecutive(
                ['magento_root/generated/code/'],
                ['magento_root/var/metadata/']
            )
            ->willReturn(true);
        $this->fileMock->expects($this->once())
            ->method('deleteDirectory')
            ->with('magento_root/var/cache/')
            ->willReturn(true);
        $this->fileMock->expects($this->any())
            ->method('copy')
            ->willThrowException(new FileSystemException('Some exception'));
        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with('Some exception');

        $this->process->execute();
    }
}
