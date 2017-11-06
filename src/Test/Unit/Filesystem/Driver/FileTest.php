<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Filesystem\Driver;

use Magento\MagentoCloud\Filesystem\Driver\File;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
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
