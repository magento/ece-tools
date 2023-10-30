<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Build;

use Magento\MagentoCloud\App\Error as AppError;
use Magento\MagentoCloud\Config\Validator\Result\Error as ResultError;
use Magento\MagentoCloud\Config\Validator\Build\OpcacheExcludePaths;
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
class OpcacheExcludePathsTest extends TestCase
{
    /**
     * @var OpcacheExcludePaths
     */
    private $opcacheExcludePaths;

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

        $this->opcacheExcludePaths = new OpcacheExcludePaths(
            $this->fileMock,
            $this->fileListMock,
            $this->resultFactoryMock
        );
    }

    /**
     * @return void
     * @throws \Magento\MagentoCloud\Config\ValidatorException
     */
    public function testValidateSuccess(): void
    {
        $phpIniPath = '/app/php.ini';
        $excludeListPath = '/app/op-exclude.txt';
        $phpIni = ['opcache.blacklist_filename' => $excludeListPath];
        $excludeList = <<<EXCLUDE
/app/*/app/etc/config.php
/app/*/app/etc/env.php
/app/app/etc/config.php
/app/app/etc/env.php
/app/etc/config.php
/app/etc/env.php
EXCLUDE;

        $this->fileListMock->expects($this->once())
            ->method('getPhpIni')
            ->willReturn($phpIniPath);
        $this->fileListMock->expects($this->once())
            ->method('getOpCacheExcludeList')
            ->willReturn($excludeListPath);
        $this->fileMock->expects($this->exactly(2))
            ->method('isExists')
            ->withConsecutive([$phpIniPath], [$excludeListPath])
            ->willReturn(true);
        $this->fileMock->expects($this->once())
            ->method('parseIni')
            ->with($phpIniPath)
            ->willReturn($phpIni);
        $this->fileMock->expects($this->once())
            ->method('fileGetContents')
            ->with($excludeListPath)
            ->willReturn($excludeList);
        $this->resultFactoryMock->expects($this->never())
            ->method('error');
        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(ResultInterface::SUCCESS)
            ->willReturn($this->createMock(Success::class));

        $this->assertInstanceOf(
            Success::class,
            $this->opcacheExcludePaths->validate()
        );
    }

    /**
     * @param int $invokeCount
     * @param bool $phpIniExists
     * @param bool $opCacheExcludeListExists
     * @return void
     * @throws \Magento\MagentoCloud\Config\ValidatorException
     * @dataProvider validateFilesDoNotExistDataProvider
     */
    public function testValidateFilesDoNotExist(
        int $invokeCount,
        bool $phpIniExists,
        bool $opCacheExcludeListExists
    ): void {
        $phpIniPath = '/app/php.ini';
        $excludeListPath = '/app/op-exclude.txt';

        $this->fileListMock->expects($this->once())
            ->method('getPhpIni')
            ->willReturn($phpIniPath);
        $this->fileListMock->expects($this->once())
            ->method('getOpCacheExcludeList')
            ->willReturn($excludeListPath);
        $this->fileMock->expects($this->exactly($invokeCount))
            ->method('isExists')
            ->withConsecutive([$phpIniPath], [$excludeListPath])
            ->willReturnOnConsecutiveCalls($phpIniExists, $opCacheExcludeListExists);
        $this->resultFactoryMock->expects($this->never())
            ->method('create');
        $this->resultFactoryMock->expects($this->once())
            ->method('error')
            ->with(
                'File php.ini or op-exclude.txt does not exist',
                'Check if your cloud template contains latest php.ini and op-exclude.txt files',
                AppError::WARN_WRONG_OPCACHE_CONFIG
            )
            ->willReturn($this->createMock(ResultError::class));

        $this->assertInstanceOf(
            ResultError::class,
            $this->opcacheExcludePaths->validate()
        );
    }

    /**
     * @return array[]
     */
    public function validateFilesDoNotExistDataProvider(): array
    {
        return [
            [
                'invokeCount' => 2,
                'phpIniExists' => true,
                'opCacheExcludeListExists' => false
            ],
            [
                'invokeCount' => 1,
                'phpIniExists' => false,
                'opCacheExcludeListExists' => true
            ],
            [
                'invokeCount' => 1,
                'phpIniExists' => false,
                'opCacheExcludeListExists' => false
            ],
        ];
    }

    /**
     * @param array|bool $phpIni
     * @return void
     * @throws \Magento\MagentoCloud\Config\ValidatorException
     * @dataProvider validatePhpIniWrongConfigurationDataProvider
     */
    public function testValidatePhpIniWrongConfiguration($phpIni): void
    {
        $phpIniPath = '/app/php.ini';
        $excludeListPath = '/app/op-exclude.txt';

        $this->fileListMock->expects($this->once())
            ->method('getPhpIni')
            ->willReturn($phpIniPath);
        $this->fileListMock->expects($this->once())
            ->method('getOpCacheExcludeList')
            ->willReturn($excludeListPath);
        $this->fileMock->expects($this->exactly(2))
            ->method('isExists')
            ->withConsecutive([$phpIniPath], [$excludeListPath])
            ->willReturn(true);
        $this->fileMock->expects($this->once())
            ->method('parseIni')
            ->with($phpIniPath)
            ->willReturn($phpIni);
        $this->resultFactoryMock->expects($this->never())
            ->method('create');
        $this->resultFactoryMock->expects($this->once())
            ->method('error')
            ->with(
                'File php.ini does not contain opcache.blacklist_filename configuration',
                'Check if your cloud template contains latest php.ini configuration file'
                    . ' https://github.com/magento/magento-cloud/blob/master/php.ini',
                AppError::WARN_WRONG_OPCACHE_CONFIG
            )
            ->willReturn($this->createMock(ResultError::class));

        $this->assertInstanceOf(
            ResultError::class,
            $this->opcacheExcludePaths->validate()
        );
    }

    /**
     * @return array
     */
    public function validatePhpIniWrongConfigurationDataProvider(): array
    {
        return [
            ['phpIni' => false],
            ['phpIni' => ['opcache.blacklist_filename' => '/tmp/some.file']],
            ['phpIni' => ['some.config' => 'some.value']],
        ];
    }

    /**
     * @return void
     * @throws \Magento\MagentoCloud\Config\ValidatorException
     */
    public function testValidateMissedPaths(): void
    {
        $phpIniPath = '/app/php.ini';
        $excludeListPath = '/app/op-exclude.txt';
        $phpIni = ['opcache.blacklist_filename' => $excludeListPath];
        $excludeList = <<<EXCLUDE
/app/app/etc/config.php
/app/app/etc/env.php
/app/etc/config.php
/app/etc/env.php
EXCLUDE;

        $this->fileListMock->expects($this->once())
            ->method('getPhpIni')
            ->willReturn($phpIniPath);
        $this->fileListMock->expects($this->once())
            ->method('getOpCacheExcludeList')
            ->willReturn($excludeListPath);
        $this->fileMock->expects($this->exactly(2))
            ->method('isExists')
            ->withConsecutive([$phpIniPath], [$excludeListPath])
            ->willReturn(true);
        $this->fileMock->expects($this->once())
            ->method('parseIni')
            ->with($phpIniPath)
            ->willReturn($phpIni);
        $this->fileMock->expects($this->once())
            ->method('fileGetContents')
            ->with($excludeListPath)
            ->willReturn($excludeList);
        $this->resultFactoryMock->expects($this->once())
            ->method('error')
            ->with(
                'File op-exclude.txt does not contain required paths to exclude for OPCache',
                'Check if your op-exclude.txt contains the next paths:' . PHP_EOL
                    . '/app/*/app/etc/config.php'. PHP_EOL .'/app/*/app/etc/env.php',
                AppError::WARN_WRONG_OPCACHE_CONFIG
            )
            ->willReturn($this->createMock(ResultError::class));

        $this->assertInstanceOf(
            ResultError::class,
            $this->opcacheExcludePaths->validate()
        );
    }
}
