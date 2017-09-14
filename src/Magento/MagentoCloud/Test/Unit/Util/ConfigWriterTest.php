<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Util;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Util\ConfigWriter;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

class ConfigWriterTest extends TestCase
{
    /**
     * @var File|Mock
     */
    private $fileMock;

    /**
     * @var DirectoryList|Mock
     */
    private $directoryListMock;

    /**
     * @var ConfigWriter
     */
    private $configWriter;


    protected function setUp()
    {
        $this->fileMock = $this->createMock(File::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);

        $this->configWriter = new ConfigWriter(
            $this->fileMock,
            $this->directoryListMock
        );
    }

    public function testWrite()
    {
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn(__DIR__ . '/_files');

        $config = ['test' => 'value'];

        $this->fileMock->expects($this->once())
            ->method('filePutContents')
            ->with(__DIR__ . '/_files/app/etc/env.php', "<?php\nreturn array (\n  'test' => 'value',\n);");

        $this->configWriter->write($config);
    }

    public function testWriteWithConfigPath()
    {
        $this->directoryListMock->expects($this->never())
            ->method('getMagentoRoot');

        $config = ['test' => 'value'];

        $this->fileMock->expects($this->once())
            ->method('filePutContents')
            ->with('/path/to/config', "<?php\nreturn array (\n  'test' => 'value',\n);");

        $this->configWriter->write($config, '/path/to/config');
    }

    public function testUpdate()
    {
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn(__DIR__ . '/_files');

        $config = ['test' => 'value'];

        $this->fileMock->expects($this->once())
            ->method('filePutContents')
            ->with(
                __DIR__ . '/_files/app/etc/env.php',
                "<?php\nreturn array (\n  'key1' => 'value1',\n  'key2' => 'value2',\n  'test' => 'value',\n);"
            );

        $this->configWriter->update($config);
    }

    public function testUpdateWithConfigPath()
    {
        $this->directoryListMock->expects($this->never())
            ->method('getMagentoRoot');

        $config = ['test' => 'value'];

        $this->fileMock->expects($this->once())
            ->method('filePutContents')
            ->with(
                __DIR__ . '/_files/app/etc/config.php',
                "<?php\nreturn array (\n  'key1' => 'value1',\n  'test' => 'value',\n);"
            );

        $this->configWriter->update($config, __DIR__ . '/_files/app/etc/config.php');
    }

    public function testGetConfigPath()
    {
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn(__DIR__ . '/_files');

        $this->assertEquals(
            __DIR__ . '/_files/app/etc/env.php',
            $this->configWriter->getConfigPath()
        );
    }
}
