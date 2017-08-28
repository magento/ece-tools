<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit;

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
        $this->directoryList = new DirectoryList(BP);
    }

    /**
     * @param string $code
     * @param string $expected
     * @dataProvider getPathDataProvider
     */
    public function testGetPath(string $code, string $expected)
    {
        $this->assertSame(
            $this->directoryList->getPath($code),
            $expected
        );
    }

    /**
     * @return array
     */
    public function getPathDataProvider(): array
    {
        return [
            'root' => [DirectoryList::ROOT, BP],
            'magento root' => [DirectoryList::MAGENTO_ROOT, realpath(BP . '/../../../')],
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

    public function testGetRoot()
    {
        $this->assertSame(
            $this->directoryList->getRoot(),
            BP
        );
    }

    public function testGetMagentoRoot()
    {
        $this->assertSame(
            $this->directoryList->getMagentoRoot(),
            realpath(BP . '/../../../')
        );
    }
}
