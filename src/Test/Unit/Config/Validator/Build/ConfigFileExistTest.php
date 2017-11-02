<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Build;

use Magento\MagentoCloud\Config\Validator\Build\ConfigFileExist;
use Magento\MagentoCloud\Config\Validator\Result;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class ConfigFileExistTest extends TestCase
{
    /**
     * @var ConfigFileExist
     */
    private $configFile;

    /**
     * @var File|Mock
     */
    private $fileMock;

    /**
     * @var FileList|Mock
     */
    private $fileListMock;

    /**
     * @var ResultFactory|Mock
     */
    private $resultFactoryMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->fileMock = $this->createMock(File::class);
        $this->fileListMock = $this->createMock(FileList::class);
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);

        $this->configFile = new ConfigFileExist(
            $this->fileMock,
            $this->fileListMock,
            $this->resultFactoryMock
        );
    }

    public function testRun()
    {
        $this->fileListMock->expects($this->once())
            ->method('getConfig')
            ->willReturn('magento_root/app/etc/config.php');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with('magento_root/app/etc/config.php')
            ->willReturn(true);
        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with('', '')
            ->willReturn($this->createMock(Result::class));

        $result = $this->configFile->validate();

        $this->assertFalse($result->hasError());
    }

    public function testRunFileNotExists()
    {
        $this->fileListMock->expects($this->once())
            ->method('getConfig')
            ->willReturn('magento_root/app/etc/config.php');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with('magento_root/app/etc/config.php')
            ->willReturn(false);
        $resultMock = $this->createMock(Result::class);
        $resultMock->expects($this->once())
            ->method('hasError')
            ->willReturn(true);
        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(
                'File app/etc/config.php not exists',
                'Please run the following commands:' . PHP_EOL
                . '1. bin/magento module:enable --all' . PHP_EOL
                . '2. git add -f app/etc/config.php' . PHP_EOL
                . '3. git commit -a -m \'adding config.php\'' . PHP_EOL
                . '4. git push'
            )
            ->willReturn($resultMock);

        $result = $this->configFile->validate();

        $this->assertTrue($result->hasError());
    }
}
