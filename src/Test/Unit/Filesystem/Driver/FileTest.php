<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Filesystem\Driver;

use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\Attributes\DataProvider;
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
    protected function setUp(): void
    {
        $this->shellMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Filesystem\Driver',
            'exec'
        );

        $this->driver = new File();
    }

    /**
     * Test copy directory.
     *
     * @param string $source
     * @param string $destination
     * @dataProvider copyDirectoryDataProvider
     * @return void
     * @throws FileSystemException
     */
    #[DataProvider('copyDirectoryDataProvider')]
    public function testCopyDirectory(string $source, string $destination): void
    {
        $execCommand = "/bin/bash -c 'shopt -s dotglob; cp -R '\''source'\''/* '\''destination'\''/'";

        $this->shellMock->expects($this->once())
            ->willReturnCallback(function ($cmd, &$output, &$status) use ($execCommand) {
                $this->assertSame($execCommand, $cmd);
                $status = 0;
                $output = null;

                return '';
            });

        $this->driver->copyDirectory(
            $source,
            $destination
        );
    }

    /**
     * Test copy directory with error method.
     *
     * @param string $source
     * @param string $destination
     * @dataProvider copyDirectoryDataProvider
     * @return void
     * @throws FileSystemException
     */
    #[DataProvider('copyDirectoryDataProvider')]
    public function testCopyDirectoryWithError(string $source, string $destination): void
    {
        $this->expectExceptionMessage('The content of path "source" cannot be copied to "destination"');
        $this->expectException(FileSystemException::class);

        $execCommand = "/bin/bash -c 'shopt -s dotglob; cp -R '\''source'\''/* '\''destination'\''/'";

        $this->shellMock->expects($this->once())
            ->willReturnCallback(function ($cmd, &$output, &$status) use ($execCommand) {
                $this->assertSame($execCommand, $cmd);
                $status = 2;
                $output = null;

                return '';
            });

        $this->driver->copyDirectory(
            $source,
            $destination
        );
    }

    /**
     * Data provider for copy directory method.
     *
     * @return array
     */
    public static function copyDirectoryDataProvider(): array
    {
        return [
            [
                'source'      => 'source',
                'destination' => 'destination',
            ],
        ];
    }

    /**
     * Test is exists method.
     *
     * @return void
     */
    public function testIsExists(): void
    {
        $fileExistsMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Filesystem\Driver',
            'file_exists'
        );
        $fileExistsMock->expects($this->once())
            ->willReturn(true);

        $this->driver->isExists('test');
    }

    /**
     * Test is link method.
     *
     * @return void
     * @throws FileSystemException
     */
    public function testIsLink(): void
    {
        $isLinkMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Filesystem\Driver',
            'is_link'
        );
        $isLinkMock->expects($this->once())
            ->willReturn(true);

        $this->driver->isLink('test');
    }

    /**
     * Test is directory method.
     *
     * @return void
     */
    public function testIsDirectory(): void
    {
        $isDirMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Filesystem\Driver',
            'is_dir'
        );
        $isDirMock->expects($this->once())
            ->willReturn(false);

        $this->driver->isDirectory('test');
    }

    /**
     * Test unlink method.
     *
     * @return void
     * @throws FileSystemException
     */
    public function testUnLink(): void
    {
        $unlinkMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Filesystem\Driver',
            'unlink'
        );
        $unlinkMock->expects($this->once())
            ->willReturn(true);

        $this->driver->unLink('test');
    }

    /**
     * Test parse ini method.
     *
     * @return void
     * @throws FileSystemException
     */
    public function testParseIni(): void
    {
        $parseIniFileMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Filesystem\Driver',
            'parse_ini_file'
        );
        $parseIniFileMock->expects($this->once())
            ->willReturn(true);

        $this->driver->parseIni('test');
    }

    /**
     * Test create directory method.
     *
     * @return void
     * @throws FileSystemException
     */
    public function testCreateDirectory(): void
    {
        $mkDirMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Filesystem\Driver',
            'mkdir'
        );
        $mkDirMock->expects($this->once())
            ->willReturn(true);

        $this->driver->createDirectory('test');
    }

    /**
     * Test rename method.
     *
     * @return void
     * @throws FileSystemException
     */
    public function testRename(): void
    {
        $renameMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Filesystem\Driver',
            'rename'
        );
        $renameMock->expects($this->once())
            ->willReturn(true);

        $this->driver->rename('test', 'testnewpath');
    }

    /**
     * Test copy method.
     *
     * @return void
     * @throws FileSystemException
     */
    public function testCopy(): void
    {
        $copyMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Filesystem\Driver',
            'copy'
        );
        $copyMock->expects($this->once())
            ->willReturn(true);

        $this->driver->copy('source', 'destination');
    }

    /**
     * Test is empty directory method.
     *
     * @return void
     */
    public function testIsEmptyDirectory(): void
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

    /**
     * Test symlink method.
     *
     * @return void
     * @throws FileSystemException
     */
    public function testSymlink(): void
    {
        $symLinkMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Filesystem\Driver',
            'symlink'
        );
        $symLinkMock->expects($this->once())
            ->willReturn(true);

        $this->driver->symlink('source', 'destination');
    }

    /**
     * Test delete file method.
     *
     * @return void
     * @throws FileSystemException
     */
    public function testDeleteFile(): void
    {
        $unlinkMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Filesystem\Driver',
            'unlink'
        );
        $unlinkMock->expects($this->once())
            ->willReturn(true);

        $this->driver->deleteFile('test');
    }

    /**
     * Test touch method.
     *
     * @return void
     * @throws FileSystemException
     */
    public function testTouch(): void
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
     * Test file put contents method.
     *
     * @return void
     * @throws FileSystemException
     */
    public function testFilePutContents(): void
    {
        $this->expectException(FileSystemException::class);

        $filePutContentsMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Filesystem\Driver',
            'file_put_contents'
        );
        $filePutContentsMock->expects($this->once())
            ->willReturn(false);

        $this->driver->filePutContents('test', 'test');
    }

    /**
     * Test get real path.
     *
     * @return void
     */
    public function testGetRealPath(): void
    {
        $realpathMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Filesystem\Driver',
            'realpath'
        );
        $realpathMock->expects($this->once())
            ->willReturn(true);

        $this->driver->getRealPath('test');
    }

    /**
     * Test scan dir.
     *
     * @return void
     */
    public function testScanDir(): void
    {
        $scandirMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Filesystem\Driver',
            'scandir'
        );
        $scandirMock->expects($this->once())
            ->willReturn(true);

        $this->driver->scanDir('test');
    }

    /**
     * Test file get contents.
     *
     * @return void
     */
    public function testFileGetContents(): void
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
