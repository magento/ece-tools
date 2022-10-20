<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Filesystem;

use Magento\MagentoCloud\Filesystem\ConfigFileList;
use Magento\MagentoCloud\Filesystem\SystemList;
use PHPUnit\Framework\MockObject\MockObject;
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
     * @var SystemList|MockObject
     */
    private $systemListMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->systemListMock = $this->createMock(SystemList::class);

        $this->systemListMock->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->systemListMock->expects($this->any())
            ->method('getRoot')
            ->willReturn('root');

        $this->configFileList = new ConfigFileList($this->systemListMock);
    }

    public function testGetConfig(): void
    {
        $this->assertSame('magento_root/app/etc/config.php', $this->configFileList->getConfig());
    }

    public function testGetConfigLocal(): void
    {
        $this->assertSame('magento_root/app/etc/config.local.php', $this->configFileList->getConfigLocal());
    }

    public function testGetEnv(): void
    {
        $this->assertSame('magento_root/app/etc/env.php', $this->configFileList->getEnv());
    }

    public function testGetBuildConfig(): void
    {
        $this->assertSame('magento_root/build_options.ini', $this->configFileList->getBuildConfig());
    }

    public function testGetToolsConfig(): void
    {
        $this->assertSame('magento_root/.magento.env.yaml', $this->configFileList->getEnvConfig());
    }

    public function testGetErrorReportConfig(): void
    {
        $this->assertSame('magento_root/pub/errors/local.xml', $this->configFileList->getErrorReportConfig());
    }

    public function testGetPhpIni(): void
    {
        $this->assertSame('magento_root/php.ini', $this->configFileList->getPhpIni());
    }

    public function testGetOpCacheExcludeList(): void
    {
        $this->assertSame('magento_root/op-exclude.txt', $this->configFileList->getOpCacheExcludeList());
    }
}
