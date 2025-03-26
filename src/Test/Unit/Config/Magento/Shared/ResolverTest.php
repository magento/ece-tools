<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Magento\Shared;

use Magento\MagentoCloud\Config\Magento\Shared\Resolver;
use Magento\MagentoCloud\Filesystem\ConfigFileList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ResolverTest extends TestCase
{
    /**
     * @var Resolver
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
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->configFileListMock = $this->createMock(ConfigFileList::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);
        $this->fileMock = $this->createMock(File::class);

        $this->resolver = new Resolver(
            $this->configFileListMock,
            $this->magentoVersionMock,
            $this->fileMock
        );
    }

    /**
     * @throws UndefinedPackageException
     */
    public function testGetPath(): void
    {
        $this->configFileListMock->expects($this->once())
            ->method('getConfig')
            ->willReturn('magento_root/app/etc/config.php');
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.2')
            ->willReturn(true);

        $this->assertSame('magento_root/app/etc/config.php', $this->resolver->getPath());
    }

    /**
     * @throws UndefinedPackageException
     */
    public function testGetPath21(): void
    {
        $this->configFileListMock->expects($this->once())
            ->method('getConfigLocal')
            ->willReturn('magento_root/app/etc/config.local.php');
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.2')
            ->willReturn(false);

        $this->assertSame('magento_root/app/etc/config.local.php', $this->resolver->getPath());
    }

    /**
     * @throws UndefinedPackageException
     */
    public function testRead(): void
    {
        $this->configFileListMock->expects($this->once())
            ->method('getConfig')
            ->willReturn('magento_root/app/etc/config.php');
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.2')
            ->willReturn(true);
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with('magento_root/app/etc/config.php')
            ->willReturn(true);
        $this->fileMock->expects($this->once())
            ->method('requireFile')
            ->willReturn(['some' => 'config']);

        $this->assertSame(
            ['some' => 'config'],
            $this->resolver->read()
        );
    }

    /**
     * @throws UndefinedPackageException
     */
    public function testReadBrokenFile(): void
    {
        $this->configFileListMock->expects($this->once())
            ->method('getConfig')
            ->willReturn('magento_root/app/etc/config.php');
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.2')
            ->willReturn(true);
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with('magento_root/app/etc/config.php')
            ->willReturn(true);
        $this->fileMock->expects($this->once())
            ->method('requireFile')
            ->willReturn('');

        $this->assertSame(
            [],
            $this->resolver->read()
        );
    }

    /**
     * @throws UndefinedPackageException
     */
    public function testReadNotExists(): void
    {
        $this->configFileListMock->expects($this->once())
            ->method('getConfig')
            ->willReturn('magento_root/app/etc/config.php');
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.2')
            ->willReturn(true);
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with('magento_root/app/etc/config.php')
            ->willReturn(false);

        $this->assertSame(
            [],
            $this->resolver->read()
        );
    }
}
