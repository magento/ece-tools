<?php

namespace Magento\MagentoCloud\Test\Unit\Util;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Util\RemoteDiskIdentifier;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

class RemoteDiskIdentifierTest extends TestCase
{

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
     * @var RemoteDiskIdentifier|Mock
     */
    private $remoteDiskIdentifier;

    /**
     * Setup the test environment.
     */
    protected function setUp()
    {
        $this->shellMock = $this->getMockBuilder(ShellInterface::class)
            ->getMockForAbstractClass();
        $this->fileMock = $this->createMock(File::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);

        $this->remoteDiskIdentifier = new RemoteDiskIdentifier(
            $this->shellMock,
            $this->fileMock,
            $this->directoryListMock
        );
    }


    public function testIsOnRemoteDisk()
    {
        $path = 'some_path';
        $magentoRoot = 'magento_root';

        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($magentoRoot);
        $this->fileMock->expects($this->once())
            ->method('isDirectory')
            ->with($magentoRoot . '/' . $path)
            ->willReturn(true);
        $this->fileMock->expects($this->once())
            ->method('getRealPath')
            ->with($magentoRoot . '/' . $path)
            ->willReturn($magentoRoot . '/' . $path);
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with("df '{$magentoRoot}/{$path}'")
            ->willReturn(
                array(
                    'Filesystem   512-blocks      Used Available Capacity iused      ifree %iused  Mounted on',
                    '/dev/rbd1  1 1 1    1% 1 1    0%   /'
                )
            );

        $this->assertTrue($this->remoteDiskIdentifier->isOnRemoteDisk($path));
    }

    public function testIsOnLocalDisk()
    {
        $path = 'some_path';
        $magentoRoot = 'magento_root';

        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($magentoRoot);
        $this->fileMock->expects($this->once())
            ->method('isDirectory')
            ->with($magentoRoot . '/' . $path)
            ->willReturn(true);
        $this->fileMock->expects($this->once())
            ->method('getRealPath')
            ->with($magentoRoot . '/' . $path)
            ->willReturn($magentoRoot . '/' . $path);
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with("df '{$magentoRoot}/{$path}'")
            ->willReturn(
                array(
                    'Filesystem   512-blocks      Used Available Capacity iused      ifree %iused  Mounted on',
                    '/dev/disk0s2  1 1 1    1% 1 1    0%   /'
                )
            );
        $this->assertTrue($this->remoteDiskIdentifier->isOnLocalDisk($path));
    }
}
