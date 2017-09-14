<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Util;

use Magento\MagentoCloud\Config\Deploy as DeployConfig;
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
     * @var DeployConfig|Mock
     */
    private $deployConfigMock;

    /**
     * @var ConfigWriter
     */
    private $configWriter;


    protected function setUp()
    {
        $this->fileMock = $this->createMock(File::class);
        $this->deployConfigMock = $this->createMock(DeployConfig::class);

        $this->configWriter = new ConfigWriter(
            $this->fileMock,
            $this->deployConfigMock
        );
    }

    public function testWrite()
    {
        $this->deployConfigMock->expects($this->once())
            ->method('getConfigFilePath')
            ->willReturn(__DIR__ . '/_files/app/etc/env.php');

        $config = ['test' => 'value'];

        $this->fileMock->expects($this->once())
            ->method('filePutContents')
            ->with(__DIR__ . '/_files/app/etc/env.php', "<?php\nreturn array (\n  'test' => 'value',\n);");

        $this->configWriter->write($config);
    }

    public function testWriteWithConfigPath()
    {
        $this->deployConfigMock->expects($this->never())
            ->method('getConfigFilePath');

        $config = ['test' => 'value'];

        $this->fileMock->expects($this->once())
            ->method('filePutContents')
            ->with('/path/to/config', "<?php\nreturn array (\n  'test' => 'value',\n);");

        $this->configWriter->write($config, '/path/to/config');
    }

    public function testUpdate()
    {
        $this->deployConfigMock->expects($this->once())
            ->method('getConfigFilePath')
            ->willReturn(__DIR__ . '/_files/app/etc/env.php');

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
        $this->deployConfigMock->expects($this->never())
            ->method('getConfigFilePath');

        $config = ['test' => 'value'];

        $this->fileMock->expects($this->once())
            ->method('filePutContents')
            ->with(
                __DIR__ . '/_files/app/etc/config.php',
                "<?php\nreturn array (\n  'key1' => 'value1',\n  'test' => 'value',\n);"
            );

        $this->configWriter->update($config, __DIR__ . '/_files/app/etc/config.php');
    }
}
