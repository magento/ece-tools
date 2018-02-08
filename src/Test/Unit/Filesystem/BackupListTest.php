<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Filesystem;

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
     * @var BackupList
     */
    private $fileBackupList;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->fileListMock = $this->createMock(FileList::class);
        $this->fileBackupList = new BackupList($this->fileListMock);
    }

    public function testGetList()
    {
        $env = '/some/path/env.php';
        $config = '/some/path/config.php';

        $this->fileListMock->expects($this->once())
            ->method('getEnv')
            ->willReturn($env);
        $this->fileListMock->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        $this->assertSame(
            [
                'app/etc/env.php' => $env,
                'app/etc/config.php' => $config,
            ],
            $this->fileBackupList->getList()
        );
    }
}
