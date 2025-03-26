<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Filesystem;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\SystemList;
use Magento\MagentoCloud\Package\MagentoVersion;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class DirectoryListTest extends TestCase
{
    public function testGetPathWithException()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Code some_code is not registered');

        $this->get22DirectoryList()->getPath('some_code');
    }

    public function testGetRoot()
    {
        $directoryList = $this->get22DirectoryList();

        $this->assertSame(
            __DIR__ . '/_files/bp',
            $directoryList->getRoot()
        );
    }

    public function testGetMagentoRoot()
    {
        $directoryList = $this->get22DirectoryList();

        $this->assertSame(
            __DIR__,
            $directoryList->getMagentoRoot()
        );
    }

    public function testGetInit()
    {
        $directoryList = $this->get22DirectoryList();

        $this->assertSame(
            __DIR__ . '/init',
            $directoryList->getInit()
        );
    }

    public function testGetVar()
    {
        $directoryList = $this->get22DirectoryList();

        $this->assertSame(
            __DIR__ . '/var',
            $directoryList->getVar()
        );
    }

    public function testGetLog()
    {
        $directoryList = $this->get22DirectoryList();

        $this->assertSame(
            __DIR__ . '/var/log',
            $directoryList->getLog()
        );
    }

    public function testGetDockerRoot()
    {
        $this->assertSame(__DIR__ . '/.docker', $this->get22DirectoryList()->getDockerRoot());
    }

    /**
     * @param DirectoryList $directoryList
     * @param string $path
     * @dataProvider getGeneratedCodeDataProvider
     */
    public function testGetGeneratedCode(DirectoryList $directoryList, string $path)
    {
        $this->assertSame($path, $directoryList->getGeneratedCode());
    }

    /**
     * @return array
     */
    public function getGeneratedCodeDataProvider(): array
    {
        return [
            [$this->get21DirectoryList(), __DIR__ . '/var/generation'],
            [$this->get22DirectoryList(), __DIR__ . '/generated/code'],
        ];
    }

    /**
     * @param DirectoryList $directoryList
     * @param string $path
     * @dataProvider getGeneratedMetadataDataProvider
     */
    public function testGetGeneratedMetadata(DirectoryList $directoryList, string $path)
    {
        $this->assertSame($path, $directoryList->getGeneratedMetadata());
    }

    /**
     * @return array
     */
    public function getGeneratedMetadataDataProvider(): array
    {
        return [
            [$this->get21DirectoryList(), __DIR__ . '/var/di'],
            [$this->get22DirectoryList(), __DIR__ . '/generated/metadata'],
        ];
    }

    /**
     * @param DirectoryList $directoryList
     * @param array $paths
     * @dataProvider getWritableDirectoriesDataProvider
     */
    public function testGetWritableDirectories(DirectoryList $directoryList, array $paths)
    {
        $result = $directoryList->getWritableDirectories();
        sort($result);
        sort($paths);
        $this->assertSame($paths, $result);
    }

    /**
     * @return array
     */
    public function getWritableDirectoriesDataProvider(): array
    {
        $relative21Paths = [
            'var/di',
            'var/generation',
            'var/log',
            'var/view_preprocessed',
            'app/etc',
            'pub/media',
        ];

        $relative22Paths = [
            'var/view_preprocessed',
            'var/log',
            'app/etc',
            'pub/media'
        ];

        return [
            [$this->get21DirectoryList(), $relative21Paths],
            [$this->get22DirectoryList(), $relative22Paths],
        ];
    }

    /**
     * @param DirectoryList $directoryList
     * @return void
     * @dataProvider getDirectoryLists
     */
    public function testGetMountPoints(DirectoryList $directoryList)
    {
        $paths = [
            'app/etc',
            'pub/media',
            'pub/static',
            'var'
        ];
        $result = $directoryList->getMountPoints();
        sort($result);
        sort($paths);
        $this->assertSame($paths, $result);
    }

    /**
     * @param DirectoryList $directoryList
     * @dataProvider getDirectoryLists
     */
    public function testGetPatches(DirectoryList $directoryList)
    {
        $this->assertSame(
            __DIR__ . '/_files/bp/patches',
            $directoryList->getPatches()
        );
    }

    /**
     * @param DirectoryList $directoryList
     * @dataProvider getDirectoryLists
     */
    public function testGetViews(DirectoryList $directoryList)
    {
        $this->assertSame(
            __DIR__ . '/_files/bp/views',
            $directoryList->getViews()
        );
    }

    /**
     * Data Provider returning both directory lists
     *
     * @return array
     */
    public function getDirectoryLists()
    {
        return [
            [
                $this->get21DirectoryList(),
            ],
            [
                $this->get22DirectoryList(),
            ],
        ];
    }

    /**
     * @return DirectoryList
     */
    private function get21DirectoryList(): DirectoryList
    {
        $magentoVersionMock = $this->createMock(MagentoVersion::class);
        $systemMock = $this->createMock(SystemList::class);

        $magentoVersionMock->method('satisfies')
            ->with('2.1.*')
            ->willReturn(true);
        $systemMock->expects($this->any())
            ->method('getRoot')
            ->willReturn(__DIR__ . '/_files/bp');
        $systemMock->expects($this->any())
            ->method('getMagentoRoot')
            ->willReturn(__DIR__);

        return new DirectoryList(
            $systemMock,
            $magentoVersionMock
        );
    }

    /**
     * @return DirectoryList
     */
    private function get22DirectoryList(): DirectoryList
    {
        $magentoVersionMock = $this->createMock(MagentoVersion::class);
        $systemMock = $this->createMock(SystemList::class);

        $magentoVersionMock->method('satisfies')
            ->with('2.1.*')
            ->willReturn(false);
        $systemMock->expects($this->any())
            ->method('getRoot')
            ->willReturn(__DIR__ . '/_files/bp');
        $systemMock->expects($this->any())
            ->method('getMagentoRoot')
            ->willReturn(__DIR__);

        return new DirectoryList(
            $systemMock,
            $magentoVersionMock
        );
    }
}
