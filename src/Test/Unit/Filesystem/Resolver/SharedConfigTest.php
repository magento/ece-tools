<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Filesystem\Resolver;

use Magento\MagentoCloud\Filesystem\ConfigFileList;
use Magento\MagentoCloud\Filesystem\Resolver\SharedConfig;
use Magento\MagentoCloud\Package\MagentoVersion;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class SharedConfigTest extends TestCase
{
    /**
     * @var SharedConfig
     */
    private $resolver;

    /**
     * @var ConfigFileList|MockObject
     */
    private $configFileListMock;

    /**
     * @var MagentoVersion|MockObject
     */
    private $magentoVersionMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->configFileListMock = $this->createMock(ConfigFileList::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);

        $this->resolver = new SharedConfig(
            $this->configFileListMock,
            $this->magentoVersionMock
        );
    }

    public function testResolve()
    {
        $this->configFileListMock->expects($this->once())
            ->method('getConfig')
            ->willReturn('magento_root/app/etc/config.php');
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.2')
            ->willReturn(true);

        $this->assertSame('magento_root/app/etc/config.php', $this->resolver->resolve());
    }

    public function testResolve21()
    {
        $this->configFileListMock->expects($this->once())
            ->method('getConfigLocal')
            ->willReturn('magento_root/app/etc/config.local.php');
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.2')
            ->willReturn(false);

        $this->assertSame('magento_root/app/etc/config.local.php', $this->resolver->resolve());
    }
}
