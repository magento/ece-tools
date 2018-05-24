<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Application;

use Magento\MagentoCloud\Config\Application\Reader;
use Magento\MagentoCloud\Filesystem\ConfigFileList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ReaderTest extends TestCase
{
    /**
     * @var ConfigFileList|MockObject
     */
    private $configFileListMock;

    /**
     * @var Reader
     */
    private $reader;

    protected function setUp()
    {
        $this->configFileListMock = $this->createMock(ConfigFileList::class);

        $this->reader = new Reader($this->configFileListMock, new File());
    }

    public function testRead()
    {
        $this->configFileListMock->expects($this->once())
            ->method('getAppConfig')
            ->willReturn(__DIR__ . '/_files/.magento.app.yaml');

        $this->assertEquals(
            [
                'hooks' => [
                    'post_deploy' => 'php bin/ece-tools post-deploy',
                ],
                'config' => [
                    'key' => 'value'
                ]
            ],
            $this->reader->read()
        );
    }

    /**
     * @expectedException \Symfony\Component\Yaml\Exception\ParseException
     */
    public function testReadParseError()
    {
        $this->configFileListMock->expects($this->once())
            ->method('getAppConfig')
            ->willReturn(__DIR__ . '/_files/.magento_wrong_format.app.yaml');

        $this->reader->read();
    }

    public function testFileNotExists()
    {
        $this->configFileListMock->expects($this->once())
            ->method('getAppConfig')
            ->willReturn(__DIR__ . '/_files/.not_exists.app.yaml');

        $this->assertEquals([], $this->reader->read());
    }
}
