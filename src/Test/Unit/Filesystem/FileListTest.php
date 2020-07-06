<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Filesystem;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Filesystem\SystemList;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class FileListTest extends TestCase
{
    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @var DirectoryList|MockObject
     */
    private $directoryListMock;

    /**
     * @var SystemList|MockObject
     */
    private $systemListMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->systemListMock = $this->createMock(SystemList::class);

        $this->directoryListMock->expects($this->any())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->directoryListMock->expects($this->any())
            ->method('getRoot')
            ->willReturn('root');
        $this->directoryListMock->method('getLog')
            ->willReturn('magento_root/var/log');
        $this->directoryListMock->method('getInit')
            ->willReturn('magento_root/init');

        $this->fileList = new FileList(
            $this->directoryListMock,
            $this->systemListMock
        );
    }

    /**
     * @throws UndefinedPackageException
     */
    public function testGetCloudLog(): void
    {
        $this->assertSame('magento_root/var/log/cloud.log', $this->fileList->getCloudLog());
    }

    /**
     * @throws UndefinedPackageException
     */
    public function testGetCloudErrorLog(): void
    {
        $this->assertSame('magento_root/var/log/cloud.error.log', $this->fileList->getCloudErrorLog());
    }

    /**
     * @throws UndefinedPackageException
     */
    public function testGetInitCloudLog(): void
    {
        $this->assertSame('magento_root/init/var/log/cloud.log', $this->fileList->getInitCloudLog());
    }

    /**
     * @throws UndefinedPackageException
     */
    public function testGetInitCloudErrorLog(): void
    {
        $this->directoryListMock->expects($this->once())
            ->method('getPath')
            ->with(DirectoryList::DIR_LOG, true)
            ->willReturn('var/log');

        $this->assertSame('magento_root/init/var/log/cloud.error.log', $this->fileList->getInitCloudErrorLog());
    }

    public function testGetPatches(): void
    {
        $this->assertSame('root/patches.json', $this->fileList->getPatches());
    }

    /**
     * @throws UndefinedPackageException
     */
    public function testGetInstallUpgradeLog(): void
    {
        $this->assertSame('magento_root/var/log/install_upgrade.log', $this->fileList->getInstallUpgradeLog());
    }

    public function testGetMagentoComposer(): void
    {
        $this->assertSame('magento_root/composer.json', $this->fileList->getMagentoComposer());
    }

    public function testGetMagentoDockerCompose(): void
    {
        $this->assertSame('magento_root/docker-compose.yml', $this->fileList->getMagentoDockerCompose());
    }

    public function testGetToolsDockerCompose(): void
    {
        $this->assertSame('root/docker-compose.yml', $this->fileList->getToolsDockerCompose());
    }

    public function testGetAppConfig(): void
    {
        $this->assertSame('magento_root/.magento.app.yaml', $this->fileList->getAppConfig());
    }

    public function testGetServicesConfig(): void
    {
        $this->assertSame('magento_root/.magento/services.yaml', $this->fileList->getServicesConfig());
    }

    /**
     * @throws UndefinedPackageException
     */
    public function testGetTtfbLog(): void
    {
        $this->assertSame('magento_root/var/log/ttfb_results.json', $this->fileList->getTtfbLog());
    }

    public function testGetEnvDistConfig(): void
    {
        $this->assertSame('magento_root/.magento.env.md', $this->fileList->getEnvDistConfig());
    }

    public function testGetServiceEolsConfig(): void
    {
        $this->assertSame('root/config/eol.yaml', $this->fileList->getServiceEolsConfig());
    }

    public function testGetFrontStaticDist(): void
    {
        $this->assertSame('root/dist/front-static.php.dist', $this->fileList->getFrontStaticDist());
    }

    public function testGetLogDistConfig(): void
    {
        $this->assertSame('root/dist/.log.env.md', $this->fileList->getLogDistConfig());
    }

    public function testGetErrorSchema(): void
    {
        $this->assertSame('root/config/schema.error.yaml', $this->fileList->getErrorSchema());
    }
}
