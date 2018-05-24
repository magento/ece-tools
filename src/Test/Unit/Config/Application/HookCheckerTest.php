<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Application;

use Magento\MagentoCloud\Config\Application\HookChecker;
use Magento\MagentoCloud\Config\Application\Reader;
use Magento\MagentoCloud\Filesystem\ConfigFileList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @inheritdoc
 */
class HookCheckerTest extends TestCase
{
    /**
     * @var ConfigFileList|MockObject
     */
    private $configFileListMock;

    /**
     * @var HookChecker
     */
    private $checker;

    protected function setUp()
    {
        $this->configFileListMock = $this->createMock(ConfigFileList::class);

        $reader = new Reader($this->configFileListMock, new File());
        $this->checker = new HookChecker($reader);
    }

    public function testIsPostDeployHookEnabled()
    {
        $this->configFileListMock->expects($this->once())
            ->method('getAppConfig')
            ->willReturn(__DIR__ . '/_files/.magento.app.yaml');

        $this->assertTrue($this->checker->isPostDeployHookEnabled());
    }

    public function testIsPostDeployHookNotEnabled()
    {
        $this->configFileListMock->expects($this->once())
            ->method('getAppConfig')
            ->willReturn(__DIR__ . '/_files/.magento_no_post_deploy.app.yaml');

        $this->assertFalse($this->checker->isPostDeployHookEnabled());
    }

    public function testIsPostDeployHookEnabledAndEceToolsNotConfigured()
    {
        $this->configFileListMock->expects($this->once())
            ->method('getAppConfig')
            ->willReturn(__DIR__ . '/_files/.magento_no_ece_tools_in_post_deploy.app.yaml');

        $this->assertFalse($this->checker->isPostDeployHookEnabled());
    }
}
