<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Filesystem;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class DirectoryListTest extends TestCase
{
    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->directoryList = new DirectoryList(
            __DIR__ . '/_files/bp',
            __DIR__,
            ['empty_path' => [], 'test_var' => [DirectoryList::PATH => '_files/test/var']]
        );
    }

    /**
     * @param string $code
     * @param string $expected
     * @dataProvider getPathDataProvider
     */
    public function testGetPath(string $code, string $expected)
    {
        $this->assertSame(
            $expected,
            $this->directoryList->getPath($code)
        );
    }

    /**
     * @return array
     */
    public function getPathDataProvider(): array
    {
        return [
            'test var' => [
                'test_var',
                __DIR__ . '/_files/test/var',
            ],
        ];
    }

    /**
     * @expectedExceptionMessage Code some_code is not registered
     * @expectedException \RuntimeException
     */
    public function testGetPathWithException()
    {
        $this->directoryList->getPath('some_code');
    }

    /**
     * @expectedExceptionMessage Config var "path" does not exists
     * @expectedException \RuntimeException
     */
    public function testGetPathWithEmptyPathException()
    {
        $this->directoryList->getPath('empty_path');
    }

    public function testGetRoot()
    {
        $this->assertSame(
            __DIR__ . '/_files/bp',
            $this->directoryList->getRoot()
        );
    }

    public function testGetMagentoRoot()
    {
        $this->assertSame(
            __DIR__,
            $this->directoryList->getMagentoRoot()
        );
    }

    public function testGetInit()
    {
        $this->assertSame(
            __DIR__ . '/init',
            $this->directoryList->getInit()
        );
    }

    public function testGetVar()
    {
        $this->assertSame(
            __DIR__ . '/var',
            $this->directoryList->getVar()
        );
    }

    public function testGetLog()
    {
        $this->assertSame(
            __DIR__ . '/var/log',
            $this->directoryList->getLog()
        );
    }

    public function testGetGenerated()
    {
        $this->assertSame(
            __DIR__ . '/generated',
            $this->directoryList->getGenerated()
        );
    }

    public function testGetGeneratedCode()
    {
        $this->assertSame(
            __DIR__ . '/generated/code',
            $this->directoryList->getGeneratedCode()
        );
    }

    public function testGetGeneratedMetadata()
    {
        $this->assertSame(
            __DIR__ . '/generated/metadata',
            $this->directoryList->getGeneratedMetadata()
        );
    }
}
