<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Filesystem\Driver;

use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 *
 * @inheritdoc
 */
class FileTest extends TestCase
{
    use \phpmock\phpunit\PHPMock;

    /**
     * @var Mock
     */
    private $shellMock;

    /**
     * @var Mock
     */
    private $fileExistsMock;

    /**
     * @var Mock
     */
    private $isLinkMock;

    /**
     * @var Mock
     */
    private $isDirMock;

    /**
     * @var Mock
     */
    private $unlinkMock;

    /**
     * @var Mock
     */
    private $parseIniFileMock;

    /**
     * @var Mock
     */
    private $mkDirMock;

    /**
     * @var Mock
     */
    private $renameMock;

    /**
     * @var Mock
     */
    private $copyMock;

    /**
     * @var Mock
     */
    private $scanDirMock;

    /**
     * @var Mock
     */
    private $symLinkMock;

    /**
     * @var Mock
     */
    private $rmDirMock;

    /**
     * @var Mock
     */
    private $touchMock;

    /**
     * @var Mock
     */
    private $filePutContentsMock;

    /**
     * @var Mock
     */
    private $realPathMock;

    /**
     * @var Mock
     */
    private $fileGetContentsMock;

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
        $this->fileExistsMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Filesystem\Driver',
            'file_exists'
        );
        $this->isLinkMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Filesystem\Driver',
            'is_link'
        );
        $this->isDirMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Filesystem\Driver',
            'is_dir'
        );
        $this->unlinkMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Filesystem\Driver',
            'unlink'
        );
        $this->parseIniFileMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Filesystem\Driver',
            'parse_ini_file'
        );
        $this->mkDirMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Filesystem\Driver',
            'mkdir'
        );
        $this->renameMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Filesystem\Driver',
            'rename'
        );
        $this->copyMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Filesystem\Driver',
            'copy'
        );
        $this->scanDirMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Filesystem\Driver',
            'scandir'
        );
        $this->symLinkMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Filesystem\Driver',
            'symlink'
        );
        $this->rmDirMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Filesystem\Driver',
            'rmdir'
        );
        $this->touchMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Filesystem\Driver',
            'touch'
        );
        $this->filePutContentsMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Filesystem\Driver',
            'file_put_contents'
        );
        $this->realPathMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Filesystem\Driver',
            'realpath'
        );
        $this->fileGetContentsMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Filesystem\Driver',
            'file_get_contents'
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
                '/bin/bash -c \'shopt -s dotglob; cp -R \'\\\'\'source\'\\\'\'/* \'\\\'\'destination\'\\\'\'/\'',
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
        $this->fileExistsMock->expects($this->once())->willReturn(true);
        $this->driver->isExists("test");
    }

    public function testIsLink()
    {
        $this->isLinkMock->expects($this->once())->willReturn(true);
        $this->driver->isLink("test");
    }

    public function testIsDirectory()
    {
        $this->isDirMock->expects($this->once())->willReturn(false);
        $this->driver->isDirectory("test");
    }

    public function testUnLink()
    {
        $this->unlinkMock->expects($this->once())->willReturn(true);
        $this->driver->unLink("test");
    }

    public function testParseIni()
    {
        $this->parseIniFileMock->expects($this->once())->willReturn(true);
        $this->driver->parseIni("test");
    }

    public function testCreateDirectory()
    {
        $this->mkDirMock->expects($this->once())->willReturn(true);
        $this->driver->createDirectory("test");
    }

    /* TODO: Figure out how to test this one.
    public function testReadDirectory()
    {
        $this->isLinkMock->expects($this->once());
        $this->driver->isLink("test");
    }
    */

    public function testRename()
    {
        $this->renameMock->expects($this->once())->willReturn(true);
        $this->driver->rename("test", "testnewpath");
    }

    public function testCopy()
    {
        $this->copyMock->expects($this->once())->willReturn(true);
        $this->driver->copy("source", "destination");
    }

    public function testIsEmptyDirectory()
    {
        $this->isDirMock->expects($this->once())->willReturn(true);
        $this->scanDirMock->expects($this->once())->willReturn(false);
        $this->driver->isEmptyDirectory("test");
    }

    public function testSymlink()
    {
        $this->symLinkMock->expects($this->once())->willReturn(true);
        $this->driver->symlink("source", "destination");
    }

    public function testDeleteFile()
    {
        $this->unlinkMock->expects($this->once())->willReturn(true);
        $this->driver->deleteFile("test");
    }

    /* TODO: This one is more complicated like testReadDirectory
    public function testDeleteDirectory()
    {
        $this->rmDirMock->expects($this->once());
        $this->driver->deleteDirectory("test");
    }

    public function testClearDirectory()
    {
        $this->isLinkMock->expects($this->once());
        $this->driver->isLink("test");
    }

    public function testBackgroundClearDirectory()
    {
        $this->isLinkMock->expects($this->once());
        $this->driver->isLink("test");
    }
    */

    public function testTouch()
    {
        $this->touchMock->expects($this->once())->willReturn(true);
        $this->driver->touch("test");
    }

    public function testFilePutContents()
    {
        $this->filePutContentsMock->expects($this->once())->willReturn(false);
        $this->expectException(FileSystemException::class);
        $this->driver->filePutContents("test", "test");
    }

    public function testGetRealPath()
    {
        $this->realPathMock->expects($this->once())->willReturn(true)->willReturn(true);
        $this->driver->getRealPath("test");
    }
    public function testScanDir()
    {
        $this->scanDirMock->expects($this->once())->willReturn(true);
        $this->driver->scanDir("test");
    }

    /* TODO: Figure out how to test this one
    public function testGetDirectoryIterator()
    {
        $this->isLinkMock->expects($this->once());
        $this->driver->isLink("test");
    }
    */

    /* TODO: I think it may be impossible to unit test "require" statement
    public function testRequireFile()
    {
        $this->isLinkMock->expects($this->once());
        $this->driver->isLink("test");
    }
    */

    public function testFileGetContents()
    {
        $this->fileGetContentsMock->expects($this->once())->willReturn(true);
        $this->driver->fileGetContents("test");
    }
}
