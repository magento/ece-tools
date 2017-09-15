<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Build;

use Magento\MagentoCloud\Process\Build\ClearInitDirectory;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class ClearInitDirectoryTest extends TestCase
{
    /**
     * @var ClearInitDirectory
     */
    private $process;

    /**
     * @var File|\PHPUnit_Framework_MockObject_MockObject
     */
    private $fileMock;

    /**
     * @var DirectoryList|\PHPUnit_Framework_MockObject_MockObject
     */
    private $directoryListMock;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->fileMock = $this->createMock(File::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();

        $this->process = new ClearInitDirectory(
            $this->fileMock,
            $this->directoryListMock,
            $this->loggerMock
        );
    }

    /**
     * @param bool $isExists
     * @param int $clearDirectory
     * @param int $deleteFile
     * @dataProvider executeDataProvider
     */
    public function testExecute($isExists, $clearDirectory, $deleteFile)
    {
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Clearing temporary directory.');
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->expects($this->exactly(2))
            ->method('isExists')
            ->willReturnMap([
                ['magento_root/init/', $isExists],
                ['magento_root/app/etc/env.php', $isExists]
            ]);
        $this->fileMock->expects($this->exactly($clearDirectory))
            ->method('clearDirectory')
            ->with('magento_root/init/')
            ->willReturn(true);
        $this->fileMock->expects($this->exactly($deleteFile))
            ->method('deleteFile')
            ->with('magento_root/app/etc/env.php')
            ->willReturn(true);

        $this->process->execute();
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            ['isExists' => true, 'clearDirectory' => 1, 'deleteFile' => 1],
            ['isExists' => false, 'clearDirectory' => 0, 'deleteFile' => 0],
        ];
    }
}
