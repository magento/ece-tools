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
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
#[AllowMockObjectsWithoutExpectations]
class DirectoryListTest extends TestCase
{
    /**
     * Test getPathWithException method.
     *
     * @return void
     */
    public function testGetPathWithException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Code some_code is not registered');

        $this->get22DirectoryList()->getPath('some_code');
    }

    /**
     * Test getRoot method.
     *
     * @return void
     */
    public function testGetRoot(): void
    {
        $directoryList = $this->get22DirectoryList();

        $this->assertSame(
            __DIR__ . '/_files/bp',
            $directoryList->getRoot()
        );
    }

    /**
     * Test getMagentoRoot method.
     *
     * @return void
     */
    public function testGetMagentoRoot(): void
    {
        $directoryList = $this->get22DirectoryList();

        $this->assertSame(
            __DIR__,
            $directoryList->getMagentoRoot()
        );
    }

    /**
     * Test getInit method.
     *
     * @return void
     */
    public function testGetInit(): void
    {
        $directoryList = $this->get22DirectoryList();

        $this->assertSame(
            __DIR__ . '/init',
            $directoryList->getInit()
        );
    }

    /**
     * Test getVar method.
     *
     * @return void
     */
    public function testGetVar(): void
    {
        $directoryList = $this->get22DirectoryList();

        $this->assertSame(
            __DIR__ . '/var',
            $directoryList->getVar()
        );
    }

    /**
     * Test getLog method.
     *
     * @return void
     */
    public function testGetLog(): void
    {
        $directoryList = $this->get22DirectoryList();

        $this->assertSame(
            __DIR__ . '/var/log',
            $directoryList->getLog()
        );
    }

    /**
     * Test getDockerRoot method.
     *
     * @return void
     */
    public function testGetDockerRoot(): void
    {
        $this->assertSame(__DIR__ . '/.docker', $this->get22DirectoryList()->getDockerRoot());
    }

    /**
     * Test getGeneratedCode method.
     *
     * @param string $version
     * @param string $path
     * @dataProvider getGeneratedCodeDataProvider
     * @return void
     */
    #[DataProvider('getGeneratedCodeDataProvider')]
    public function testGetGeneratedCode(string $version, string $path): void
    {
        $directoryList = $version === '2.1' ? $this->get21DirectoryList() : $this->get22DirectoryList();
        $this->assertSame($path, $directoryList->getGeneratedCode());
    }

    /**
     * Data provider for getGeneratedCode method.
     *
     * @return array
     */
    public static function getGeneratedCodeDataProvider(): array
    {
        return [
            ['2.1', __DIR__ . '/var/generation'],
            ['2.2', __DIR__ . '/generated/code'],
        ];
    }

    /**
     * Test getGeneratedMetadata method.
     *
     * @param string $version
     * @param string $path
     * @dataProvider getGeneratedMetadataDataProvider
     * @return void
     */
    #[DataProvider('getGeneratedMetadataDataProvider')]
    public function testGetGeneratedMetadata(string $version, string $path): void
    {
        $directoryList = $version === '2.1' ? $this->get21DirectoryList() : $this->get22DirectoryList();
        $this->assertSame($path, $directoryList->getGeneratedMetadata());
    }

    /**
     * Data provider for getGeneratedMetadata method.
     *
     * @return array
     */
    public static function getGeneratedMetadataDataProvider(): array
    {
        return [
            ['2.1', __DIR__ . '/var/di'],
            ['2.2', __DIR__ . '/generated/metadata'],
        ];
    }

    /**
     * Test getWritableDirectories method.
     *
     * @param string $version
     * @param array $paths
     * @dataProvider getWritableDirectoriesDataProvider
     * @return void
     */
    #[DataProvider('getWritableDirectoriesDataProvider')]
    public function testGetWritableDirectories(string $version, array $paths): void
    {
        $directoryList = $version === '2.1' ? $this->get21DirectoryList() : $this->get22DirectoryList();
        $result = $directoryList->getWritableDirectories();
        sort($result);
        sort($paths);
        $this->assertSame($paths, $result);
    }

    /**
     * Data provider for getWritableDirectories method.
     *
     * @return array
     */
    public static function getWritableDirectoriesDataProvider(): array
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
            [
                'version' => '2.1',
                'paths'   => $relative21Paths,
            ],
            [
                'version' => '2.2',
                'paths'   => $relative22Paths,
            ],
        ];
    }

    /**
     * Test getMountPoints method.
     *
     * @param string $version
     * @return void
     * @dataProvider getDirectoryLists
     */
    #[DataProvider('getDirectoryLists')]
    public function testGetMountPoints(string $version): void
    {
        $directoryList = $version === '2.1' ? $this->get21DirectoryList() : $this->get22DirectoryList();
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
     * Test getPatches method.
     *
     * @param string $version
     * @dataProvider getDirectoryLists
     * @return void
     */
    #[DataProvider('getDirectoryLists')]
    public function testGetPatches(string $version): void
    {
        $directoryList = $version === '2.1' ? $this->get21DirectoryList() : $this->get22DirectoryList();
        $this->assertSame(
            __DIR__ . '/_files/bp/patches',
            $directoryList->getPatches()
        );
    }

    /**
     * Test getViews method.
     *
     * @param string $version
     * @dataProvider getDirectoryLists
     * @return void
     */
    #[DataProvider('getDirectoryLists')]
    public function testGetViews(string $version): void
    {
        $directoryList = $version === '2.1' ? $this->get21DirectoryList() : $this->get22DirectoryList();
        $this->assertSame(
            __DIR__ . '/_files/bp/views',
            $directoryList->getViews()
        );
    }

    /**
     * Data Provider returning both directory list versions
     *
     * @return array
     */
    public static function getDirectoryLists(): array
    {
        return [
            ['2.1'],
            ['2.2'],
        ];
    }

    /**
     * Test get21DirectoryList method.
     *
     * @return DirectoryList
     */
    private function get21DirectoryList(): DirectoryList
    {
        $magentoVersionMock = $this->createStub(MagentoVersion::class);
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
     * Test get22DirectoryList method.
     *
     * @return DirectoryList
     */
    private function get22DirectoryList(): DirectoryList
    {
        $magentoVersionMock = $this->createStub(MagentoVersion::class);
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
