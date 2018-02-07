<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Build;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Process\Build\MarshallFiles;
use PHPUnit\Framework\TestCase;

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
        $this->fileMock = $this->getMockBuilder(File::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->directoryListMock = $this->getMockBuilder(DirectoryList::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->directoryListMock->method('getMagentoRoot')
            ->willReturn('magento_root');

        $this->process = new MarshallFiles(
            $this->fileMock,
            $this->directoryListMock
        );
    }

    /**
     * @param bool $isExist
     * @param int $deleteDirectory
     * @param int $createDirectory
     * @dataProvider executeDataProvider
     */
    public function testExecute($isExist, $deleteDirectory, $createDirectory)
    {
        $enterpriseFolder = 'magento_root/app/enterprise';
        $varCache = 'magento_root/var/cache/';

        $this->fileMock->expects($this->exactly($deleteDirectory))
            ->method('deleteDirectory')
            ->with($varCache)
            ->willReturn(true);
        $this->fileMock->expects($this->exactly($createDirectory))
            ->method('createDirectory')
            ->with($enterpriseFolder, 0777)
            ->willReturn(true);
        $this->fileMock->expects($this->exactly(2))
            ->method('copy')
            ->withConsecutive(
                ['magento_root/app/etc/di.xml', 'magento_root/app/di.xml'],
                ['magento_root/app/etc/enterprise/di.xml', 'magento_root/app/enterprise/di.xml']
            );
        $this->fileMock->expects($this->exactly(3))
            ->method('isExists')
            ->willReturnMap([
                [$varCache, $isExist],
                [$enterpriseFolder, $isExist],
                ['magento_root/app/etc/enterprise/di.xml', true],
            ]);

        $this->process->execute();
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            ['isExist' => true, 'deleteDirectory' => 1, 'createDirectory' => 0],
            ['isExist' => false, 'deleteDirectory' => 0, 'createDirectory' => 1],
        ];
    }
}
