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
    /**
     * @param string $code
     * @param string $expected
     */
    public function testGetPath()
    {
        $directoryList = $this->get22DirectoryListWithIsGreator();

        $this->assertSame(
            __DIR__ . '/_files/test/var',
            $directoryList->getPath('test_var')
        );
    }

    /**
     * @expectedExceptionMessage Code some_code is not registered
     * @expectedException \RuntimeException
     */
    public function testGetPathWithException()
    {
        $this->get22DirectoryListWithIsGreator()->getPath('some_code');
    }

    /**
     * @expectedExceptionMessage Config var "path" does not exists
     * @expectedException \RuntimeException
     */
    public function testGetPathWithEmptyPathException()
    {
        $this->get22DirectoryListWithIsGreator()->getPath('empty_path');
    }

    public function testGetRoot()
    {
        $directoryList = $this->get22DirectoryListWithIsGreator();

        $this->assertSame(
            __DIR__ . '/_files/bp',
            $directoryList->getRoot()
        );
    }

    public function testGetMagentoRoot()
    {
        $directoryList = $this->get22DirectoryListWithIsGreator();

        $this->assertSame(
            __DIR__,
            $directoryList->getMagentoRoot()
        );
    }

    public function testGetInit()
    {
        $directoryList = $this->get22DirectoryListWithIsGreator();

        $this->assertSame(
            __DIR__ . '/init',
            $directoryList->getInit()
        );
    }

    public function testGetVar()
    {
        $directoryList = $this->get22DirectoryListWithIsGreator();

        $this->assertSame(
            __DIR__ . '/var',
            $directoryList->getVar()
        );
    }

    public function testGetLog()
    {
        $directoryList = $this->get22DirectoryListWithIsGreator();

        $this->assertSame(
            __DIR__ . '/var/log',
            $directoryList->getLog()
        );
    }

    public function testGetGenerated()
    {
        $directoryList = $this->get22DirectoryListWithIsGreator();

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
            [$this->get21DirectoryListWithIsGreator(), __DIR__ . '/var/generation'],
            [$this->get22DirectoryListWithIsGreator(), __DIR__ . '/generated/code'],
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
            [$this->get21DirectoryListWithIsGreator(), __DIR__ . '/var/di'],
            [$this->get22DirectoryListWithIsGreator(), __DIR__ . '/generated/metadata'],
        ];
    }

    /**
     * @param DirectoryList $directoryList
     * @param array $paths
     * @dataProvider getWritableDirectoriesDataProvider
     */
    public function testGetGetWritableDirectories(DirectoryList $directoryList, array $paths)
    {
        $result = $directoryList->getWritableDirectories();
        sort($result);
        sort($paths);
        $this->assertEquals($paths, $result);
    }

    public function getWritableDirectoriesDataProvider()
    {
        $paths21 = [
            __DIR__ . '/var/di',
            __DIR__ . '/var/generation',
            __DIR__ . '/var/view_preprocessed',
            __DIR__ . '/app/etc',
            __DIR__ . '/pub/media'
        ];

        $paths22 = [__DIR__ . '/var', __DIR__ . '/app/etc', __DIR__ . '/pub/media'];

        return [
            [$this->get21DirectoryListWithSatisfies(), $paths21],
            [$this->get22DirectoryListWithSatisfies(), $paths22],
        ];
    }

    private function get21DirectoryListWithIsGreator()
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

    private function get22DirectoryListWithIsGreator()
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
