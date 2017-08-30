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
            'root' => [DirectoryList::ROOT, __DIR__],
            'magento root' => [
                DirectoryList::MAGENTO_ROOT,
                realpath(__DIR__ . '/../../../'),
            ],
            'test var' => [
                'test_var',
                realpath(__DIR__ . '/_files/test/var'),
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
            __DIR__,
            $this->directoryList->getRoot()
        );
    }

    public function testGetMagentoRoot()
    {
        $this->assertSame(
            realpath(__DIR__ . '/../../../'),
            $this->directoryList->getMagentoRoot()
        );
    }
}
