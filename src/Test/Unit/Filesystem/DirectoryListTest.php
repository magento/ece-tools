<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Filesystem;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Package\MagentoVersion;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class DirectoryListTest extends TestCase
{
    public function testGetPath()
    {
        $directoryList = $this->get22DirectoryListWithIsGreater();

        $this->assertSame(
            __DIR__ . '/_files/test/var',
            $directoryList->getPath('test_var')
        );

        $this->assertSame(
            '_files/test/var',
            $directoryList->getPath('test_var', true)
        );
    }

    /**
     * @expectedExceptionMessage Code some_code is not registered
     * @expectedException \RuntimeException
     */
    public function testGetPathWithException()
    {
        $this->get22DirectoryListWithIsGreater()->getPath('some_code');
    }

    /**
     * @expectedExceptionMessage Config var "path" does not exists
     * @expectedException \RuntimeException
     */
    public function testGetPathWithEmptyPathException()
    {
        $this->get22DirectoryListWithIsGreater()->getPath('empty_path');
    }

    public function testGetRoot()
    {
        $directoryList = $this->get22DirectoryListWithIsGreater();

        $this->assertSame(
            __DIR__ . '/_files/bp',
            $directoryList->getRoot()
        );
    }

    public function testGetMagentoRoot()
    {
        $directoryList = $this->get22DirectoryListWithIsGreater();

        $this->assertSame(
            __DIR__,
            $directoryList->getMagentoRoot()
        );
    }

    public function testGetInit()
    {
        $directoryList = $this->get22DirectoryListWithIsGreater();

        $this->assertSame(
            __DIR__ . '/init',
            $directoryList->getInit()
        );
    }

    public function testGetVar()
    {
        $directoryList = $this->get22DirectoryListWithIsGreater();

        $this->assertSame(
            __DIR__ . '/var',
            $directoryList->getVar()
        );
    }

    public function testGetLog()
    {
        $directoryList = $this->get22DirectoryListWithIsGreater();

        $this->assertSame(
            __DIR__ . '/var/log',
            $directoryList->getLog()
        );
    }

    public function testGetGenerated()
    {
        $directoryList = $this->get22DirectoryListWithIsGreater();

        $this->assertSame(
            __DIR__ . '/generated',
            $directoryList->getGenerated()
        );
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

    public function getGeneratedCodeDataProvider(): array
    {
        return [
            [$this->get21DirectoryListWithIsGreater(), __DIR__ . '/var/generation'],
            [$this->get22DirectoryListWithIsGreater(), __DIR__ . '/generated/code'],
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

    public function getGeneratedMetadataDataProvider(): array
    {
        return [
            [$this->get21DirectoryListWithIsGreater(), __DIR__ . '/var/di'],
            [$this->get22DirectoryListWithIsGreater(), __DIR__ . '/generated/metadata'],
        ];
    }

    /**
     * @param DirectoryList $directoryList
     * @param array $paths
     * @dataProvider getWritableDirectoriesDataProvider
     */
    public function testGetGetWritableDirectories(DirectoryList $directoryList, array $paths, bool $relativePath)
    {
        $result = $directoryList->getWritableDirectories($relativePath);
        sort($result);
        sort($paths);
        $this->assertSame($paths, $result);
    }

    public function getWritableDirectoriesDataProvider()
    {
        $abs21Paths = [
            __DIR__ . '/var/di',
            __DIR__ . '/var/generation',
            __DIR__ . '/var/view_preprocessed',
            __DIR__ . '/app/etc',
            __DIR__ . '/pub/media'
        ];
        $relative21Paths = [
            'var/di',
            'var/generation',
            'var/view_preprocessed',
            'app/etc',
            'pub/media'
        ];

        $abs22Paths      = [__DIR__ . '/var', __DIR__ . '/app/etc', __DIR__ . '/pub/media'];
        $relative22Paths = ['var', 'app/etc', 'pub/media'];

        return [
            [$this->get21DirectoryListWithSatisfies(), $abs21Paths,      false],
            [$this->get22DirectoryListWithSatisfies(), $abs22Paths,      false],
            [$this->get21DirectoryListWithSatisfies(), $relative21Paths, true],
            [$this->get22DirectoryListWithSatisfies(), $relative22Paths, true],
        ];
    }

    private function get21DirectoryListWithIsGreater()
    {
        $magentoVersionMock = $this->createMock(MagentoVersion::class);

        $magentoVersionMock->method('isGreaterOrEqual')
            ->with('2.2')
            ->willReturn(false);

        return new DirectoryList(
            __DIR__ . '/_files/bp',
            __DIR__,
            $magentoVersionMock,
            ['empty_path' => [], 'test_var' => [DirectoryList::PATH => '_files/test/var']]
        );
    }

    private function get21DirectoryListWithSatisfies()
    {
        $magentoVersionMock = $this->createMock(MagentoVersion::class);

        $magentoVersionMock->method('satisfies')
            ->willReturnMap([
                ['2.1.*', true],
                ['2.2.*', false],
            ]);

        return new DirectoryList(
            __DIR__ . '/_files/bp',
            __DIR__,
            $magentoVersionMock,
            ['empty_path' => [], 'test_var' => [DirectoryList::PATH => '_files/test/var']]
        );
    }

    private function get22DirectoryListWithIsGreater()
    {
        $magentoVersionMock = $this->createMock(MagentoVersion::class);

        $magentoVersionMock->method('isGreaterOrEqual')
            ->with('2.2')
            ->willReturn(true);

        return new DirectoryList(
            __DIR__ . '/_files/bp',
            __DIR__,
            $magentoVersionMock,
            ['empty_path' => [], 'test_var' => [DirectoryList::PATH => '_files/test/var']]
        );
    }

    private function get22DirectoryListWithSatisfies()
    {
        $magentoVersionMock = $this->createMock(MagentoVersion::class);

        $magentoVersionMock->method('satisfies')
            ->willReturnMap([
                ['2.1.*', false],
                ['2.2.*', true],
            ]);

        return new DirectoryList(
            __DIR__ . '/_files/bp',
            __DIR__,
            $magentoVersionMock,
            ['empty_path' => [], 'test_var' => [DirectoryList::PATH => '_files/test/var']]
        );
    }
}
