<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Filesystem;

use Magento\MagentoCloud\Filesystem\ConfigFileList;
use Magento\MagentoCloud\Filesystem\SystemList;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ConfigFileListTest extends TestCase
{
    /**
     * @var ConfigFileList
     */
    private $configFileList;

    /**
     * @var SystemList|Mock
     */
    private $systemListMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->systemListMock = $this->createMock(SystemList::class);

        $this->systemListMock->expects($this->any())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->systemListMock->expects($this->any())
            ->method('getRoot')
            ->willReturn('root');

        $this->configFileList = new ConfigFileList($this->systemListMock);
    }

    public function testGetConfig()
    {
        $this->assertSame('magento_root/app/etc/config.php', $this->configFileList->getConfig());
    }

    public function testGetConfigLocal()
    {
        $this->assertSame('magento_root/app/etc/config.local.php', $this->configFileList->getConfigLocal());
    }

    public function testGetEnv()
    {
        $this->assertSame('magento_root/app/etc/env.php', $this->configFileList->getEnv());
    }

    public function testGetBuildConfig()
    {
        $this->assertSame('magento_root/build_options.ini', $this->configFileList->getBuildConfig());
    }

    public function testGetToolsConfig()
    {
        $this->assertSame('magento_root/.magento.env.yaml', $this->configFileList->getEnvConfig());
    }
}
