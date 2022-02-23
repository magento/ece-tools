<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Build;

use Magento\MagentoCloud\Config\Validator\Build\ConfigFileExists;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use Magento\MagentoCloud\Config\Validator\ResultInterface;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ConfigFileExistsTest extends TestCase
{
    /**
     * @var ConfigFileExists
     */
    private $configFile;

    /**
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @var FileList|MockObject
     */
    private $fileListMock;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->fileMock = $this->createMock(File::class);
        $this->fileListMock = $this->createMock(FileList::class);
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);

        $this->configFile = new ConfigFileExists(
            $this->fileMock,
            $this->fileListMock,
            $this->resultFactoryMock
        );
    }

    public function testRun(): void
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
            ->with(ResultInterface::SUCCESS)
            ->willReturn($this->createMock(Success::class));

        $result = $this->configFile->validate();

        $this->assertInstanceOf(Success::class, $result);
    }

    public function testRunFileNotExists(): void
    {
        $this->fileListMock->expects($this->once())
            ->method('getConfig')
            ->willReturn('magento_root/app/etc/config.php');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with('magento_root/app/etc/config.php')
            ->willReturn(false);
        $resultMock = $this->createMock(Error::class);
        $this->resultFactoryMock->expects($this->once())
            ->method('error')
            ->with(
                'File app/etc/config.php does not exist',
                'Please run the following commands:' . PHP_EOL
                . '1. bin/magento module:enable --all' . PHP_EOL
                . '2. git add -f app/etc/config.php' . PHP_EOL
                . '3. git commit -m \'Adding config.php\'' . PHP_EOL
                . '4. git push',
                \Magento\MagentoCloud\App\Error::WARN_CONFIG_PHP_NOT_EXISTS
            )
            ->willReturn($resultMock);

        $result = $this->configFile->validate();

        $this->assertInstanceOf(Error::class, $result);
    }
}
