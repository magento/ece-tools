<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Filesystem;

use Magento\MagentoCloud\Package\MagentoVersion;
use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Filesystem\BackupList;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class BackupListTest extends TestCase
{
    /**
     * @var FileList|Mock
     */
    private $fileListMock;

    /**
     * @var MagentoVersion|Mock
     */
    private $magentoVersionMock;

    /**
     * @var BackupList
     */
    private $fileBackupList;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->fileListMock = $this->createMock(FileList::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);

        $this->fileBackupList = new BackupList($this->fileListMock, $this->magentoVersionMock);
    }

    public function testGetList()
    {
        $env = '/some/path/env.php';
        $config = '/some/path/config.php';

        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.2')
            ->willReturn(true);
        $this->fileListMock->expects($this->once())
            ->method('getEnv')
            ->willReturn($env);
        $this->fileListMock->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);
        $this->fileListMock->expects($this->never())
            ->method('getConfigLocal');

        $this->assertSame(
            [
                'app/etc/env.php' => $env,
                'app/etc/config.php' => $config,
            ],
            $this->fileBackupList->getList()
        );
    }

    public function testGetListVersion21x()
    {
        $env = '/some/path/env.php';
        $config = '/some/path/config.php';
        $configLocal = '/some/path/config.local.php';

        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.2')
            ->willReturn(false);
        $this->fileListMock->expects($this->once())
            ->method('getEnv')
            ->willReturn($env);
        $this->fileListMock->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);
        $this->fileListMock->expects($this->once())
            ->method('getConfigLocal')
            ->willReturn($configLocal);

        $this->assertSame(
            [
                'app/etc/env.php' => $env,
                'app/etc/config.php' => $config,
                'app/etc/config.local.php' => $configLocal,
            ],
            $this->fileBackupList->getList()
        );
    }
}
