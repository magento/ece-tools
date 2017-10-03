<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Filesystem\Driver;

use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Shell\ShellInterface;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class FileTest extends TestCase
{
    /**
     * @var File
     */
    private $driver;

    /**
     * @var ShellInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shellMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->shellMock = $this->getMockForAbstractClass(ShellInterface::class);

        $this->driver = new File(
            $this->shellMock
        );
    }

    /**
     * @param string $source
     * @param string $destination
     * @dataProvider copyDirectoryDataProvider
     */
    public function testCopyDirectory(string $source, string $destination)
    {
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with(sprintf(
                '/bin/bash -c "shopt -s dotglob; cp -R %s/* %s/"',
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
}
