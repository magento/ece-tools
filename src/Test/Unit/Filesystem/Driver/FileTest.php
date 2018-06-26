<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Filesystem\Driver;

use Magento\MagentoCloud\Filesystem\Driver\File;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class FileTest extends TestCase
{
    use PHPMock;

    /**
     * @var MockObject
     */
    private $shellMock;

    /**
     * @var File
     */
    private $driver;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->shellMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Filesystem\Driver',
            'shell_exec'
        );

        $this->driver = new File();
    }

    /**
     * @param string $source
     * @param string $destination
     * @dataProvider copyDirectoryDataProvider
     */
    public function testCopyDirectory(string $source, string $destination)
    {
        $this->shellMock->expects($this->once())
            ->with(sprintf(
                "/bin/bash -c 'shopt -s dotglob; cp -R '\''%s'\''/* '\''%s'\''/'",
                $source,
                $destination
            ));

        $this->driver->copyDirectory(
            $source,
            $destination
        );
    }

    /**
     * @return array
     */
    public function copyDirectoryDataProvider(): array
    {
        return [
            ['source', 'destination'],
        ];
    }

    public function testIsExists()
    {
        $fileExistsMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Filesystem\Driver',
            'file_exists'
        );
        $fileExistsMock->expects($this->once())
            ->willReturn(true);

        $this->driver->isExists('test');
    }

    public function testIsLink()
    {
        $isLinkMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Filesystem\Driver',
            'is_link'
        );
        $isLinkMock->expects($this->once())
            ->willReturn(true);

        $this->driver->isLink('test');
    }

    public function testIsDirectory()
    {
        $isDirMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Filesystem\Driver',
            'is_dir'
        );
        $isDirMock->expects($this->once())
            ->willReturn(false);

        $this->driver->isDirectory('test');
    }

    public function testUnLink()
    {
        $unlinkMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Filesystem\Driver',
            'unlink'
        );
        $unlinkMock->expects($this->once())
            ->willReturn(true);

        $this->driver->unLink('test');
    }

    public function testParseIni()
    {
        $parseIniFileMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Filesystem\Driver',
            'parse_ini_file'
        );
        $parseIniFileMock->expects($this->once())
            ->willReturn(true);

        $this->driver->parseIni('test');
    }

    public function testCreateDirectory()
    {
        $mkDirMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Filesystem\Driver',
            'mkdir'
        );
        $mkDirMock->expects($this->once())
            ->willReturn(true);

        $this->driver->createDirectory('test');
    }

    public function testRename()
    {
        $renameMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Filesystem\Driver',
            'rename'
        );
        $renameMock->expects($this->once())
            ->willReturn(true);

        $this->driver->rename('test', 'testnewpath');
    }

    public function testCopy()
    {
        $copyMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Filesystem\Driver',
            'copy'
        );
        $copyMock->expects($this->once())
            ->willReturn(true);

        $this->driver->copy('source', 'destination');
    }

    public function testIsEmptyDirectory()
    {
        $scanDirMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Filesystem\Driver',
            'scandir'
        );
        $isDirMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Filesystem\Driver',
            'is_dir'
        );

        $isDirMock->expects($this->once())
            ->willReturn(true);
        $scanDirMock->expects($this->once())
            ->willReturn(false);

        $this->driver->isEmptyDirectory('test');
    }

    public function testSymlink()
    {
        $symLinkMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Filesystem\Driver',
            'symlink'
        );
        $symLinkMock->expects($this->once())
            ->willReturn(true);

        $this->driver->symlink('source', 'destination');
    }

    public function testDeleteFile()
    {
        $unlinkMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Filesystem\Driver',
            'unlink'
        );
        $unlinkMock->expects($this->once())
            ->willReturn(true);

        $this->driver->deleteFile('test');
    }

    public function testTouch()
    {
        $touchMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Filesystem\Driver',
            'touch'
        );
        $touchMock->expects($this->once())
            ->willReturn(true);

        $this->driver->touch('test');
    }

    /**
     * @expectedException \Magento\MagentoCloud\Filesystem\FileSystemException
     */
    public function testFilePutContents()
    {
        $filePutContentsMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Filesystem\Driver',
            'file_put_contents'
        );
        $filePutContentsMock->expects($this->once())
            ->willReturn(false);

        $this->driver->filePutContents('test', 'test');
    }

    public function testGetRealPath()
    {
        $realpathMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Filesystem\Driver',
            'realpath'
        );
        $realpathMock->expects($this->once())
            ->willReturn(true);

        $this->driver->getRealPath('test');
    }

    public function testScanDir()
    {
        $scandirMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Filesystem\Driver',
            'scandir'
        );
        $scandirMock->expects($this->once())
            ->willReturn(true);

        $this->driver->scanDir('test');
    }

    public function testFileGetContents()
    {
        $fileGetContentsMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Filesystem\Driver',
            'file_get_contents'
        );
        $fileGetContentsMock->expects($this->once())
            ->willReturn(true);

        $this->driver->fileGetContents('test');
    }
}
