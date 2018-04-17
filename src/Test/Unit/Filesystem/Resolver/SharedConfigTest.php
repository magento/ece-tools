<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Filesystem\Resolver;

use Magento\MagentoCloud\Filesystem\Resolver\SharedConfig;
use Magento\MagentoCloud\Filesystem\SystemList;
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
     * @var SystemList|MockObject
     */
    private $systemListMock;

    /**
     * @var MagentoVersion|MockObject
     */
    private $magentoVersionMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->systemListMock = $this->createMock(SystemList::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);

        $this->resolver = new SharedConfig(
            $this->systemListMock,
            $this->magentoVersionMock
        );
    }

    public function testResolve()
    {
        $this->systemListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.2')
            ->willReturn(true);

        $this->assertSame('magento_root/app/etc/config.php', $this->resolver->resolve());
    }

    public function testResolve21()
    {
        $this->systemListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.2')
            ->willReturn(false);

        $this->assertSame('magento_root/app/etc/config.local.php', $this->resolver->resolve());
    }
}
