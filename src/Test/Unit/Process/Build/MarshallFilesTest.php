<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Build;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Process\Build\MarshallFiles;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

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
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @var DirectoryList|MockObject
     */
    private $directoryListMock;

    /**
     * @var MagentoVersion|MockObject
     */
    private $magentoVersionMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->fileMock = $this->createMock(File::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);

        $this->directoryListMock->method('getMagentoRoot')
            ->willReturn('magento_root');

        $this->process = new MarshallFiles(
            $this->fileMock,
            $this->directoryListMock,
            $this->magentoVersionMock
        );
    }

    /**
     * @param bool $isExist
     * @param int $deleteDirectory
     * @param int $createDirectory
     * @dataProvider executeDataProvider
     */
    public function testExecuteForMagento2_1($isExist, $deleteDirectory, $createDirectory)
    {
        $enterpriseFolder = 'magento_root/app/enterprise';
        $varCache = 'magento_root/var/cache/';

        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.2')
            ->willReturn(false);
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

    public function testExecuteFroMagentoGreater2_2()
    {
        $varCache = 'magento_root/var/cache/';

        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.2')
            ->willReturn(true);
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($varCache)
            ->willReturn(true);
        $this->fileMock->expects($this->once())
            ->method('deleteDirectory')
            ->with($varCache)
            ->willReturn(true);
        $this->fileMock->expects($this->never())
            ->method('copy');

        $this->process->execute();
    }
}
