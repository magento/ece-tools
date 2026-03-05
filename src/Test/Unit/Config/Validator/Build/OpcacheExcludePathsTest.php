<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Build;

use Magento\MagentoCloud\App\Error as AppError;
use Magento\MagentoCloud\Config\Validator\Build\OpcacheExcludePaths;
use Magento\MagentoCloud\Config\Validator\Result\Error as ResultError;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\Validator\ResultInterface;
use Magento\MagentoCloud\Config\ValidatorException;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
#[AllowMockObjectsWithoutExpectations]
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
     * Test validateSuccess method.
     *
     * @return void
     * @throws ValidatorException
     */
    public function testValidateSuccess(): void
    {
        $phpIniPath = '/app/php.ini';
        $excludeListPath = '/app/op-exclude.txt';
        $phpIni = ['opcache.blacklist_filename' => $excludeListPath];
        $excludeList = "/app/*/app/etc/config.php\n" .
            "/app/*/app/etc/env.php\n" .
            "/app/app/etc/config.php\n" .
            "/app/app/etc/env.php\n" .
            "/app/etc/config.php\n" .
            "/app/etc/env.php";

        $this->fileListMock->expects($this->once())
            ->method('getPhpIni')
            ->willReturn($phpIniPath);
        $this->fileListMock->expects($this->once())
            ->method('getOpCacheExcludeList')
            ->willReturn($excludeListPath);
        $this->fileMock->expects($this->exactly(2))
            ->method('isExists')
            ->willReturnCallback(fn($param) => match ([$param]) {
                [$phpIniPath] => true,
                [$excludeListPath] => true
            });
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
     * Test validateFilesDoNotExist method.
     *
     * @param int $invokeCount
     * @param bool $phpIniExists
     * @param bool $opCacheExcludeListExists
     * @dataProvider validateFilesDoNotExistDataProvider
     * @return void
     * @throws ValidatorException
     */
    #[DataProvider('validateFilesDoNotExistDataProvider')]
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
            // withConsecutive() alternative.
            ->willReturnCallback(fn($param) => match ([$param]) {
                [$phpIniPath] => $phpIniExists,
                [$excludeListPath] => $opCacheExcludeListExists
            });
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
     * Data provider for validateFilesDoNotExist method.
     *
     * @return array[]
     */
    public static function validateFilesDoNotExistDataProvider(): array
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
     * Test validatePhpIniWrongConfiguration method.
     *
     * @param array|bool $phpIni
     * @dataProvider validatePhpIniWrongConfigurationDataProvider
     * @return void
     * @throws \Magento\MagentoCloud\Config\ValidatorException
     */
    #[DataProvider('validatePhpIniWrongConfigurationDataProvider')]
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
            // withConsecutive() alternative.
            ->willReturnCallback(fn($param) => match ([$param]) {
                [$phpIniPath] => true,
                [$excludeListPath] => true
            });
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
     * Data provider for validatePhpIniWrongConfiguration method.
     *
     * @return array
     */
    public static function validatePhpIniWrongConfigurationDataProvider(): array
    {
        return [
            ['phpIni' => false],
            ['phpIni' => ['opcache.blacklist_filename' => '/tmp/some.file']],
            ['phpIni' => ['some.config' => 'some.value']],
        ];
    }

    /**
     * Test validateMissedPaths method.
     *
     * @return void
     * @throws \Magento\MagentoCloud\Config\ValidatorException
     */
    public function testValidateMissedPaths(): void
    {
        $phpIniPath = '/app/php.ini';
        $excludeListPath = '/app/op-exclude.txt';
        $phpIni = ['opcache.blacklist_filename' => $excludeListPath];
        $excludeList = "/app/app/etc/config.php\n" .
            "/app/app/etc/env.php\n" .
            "/app/etc/config.php\n" .
            "/app/etc/env.php";

        $this->fileListMock->expects($this->once())
            ->method('getPhpIni')
            ->willReturn($phpIniPath);
        $this->fileListMock->expects($this->once())
            ->method('getOpCacheExcludeList')
            ->willReturn($excludeListPath);
        $this->fileMock->expects($this->exactly(2))
            ->method('isExists')
            // withConsecutive() alternative.
            ->willReturnCallback(fn($param) => match ([$param]) {
                [$phpIniPath] => true,
                [$excludeListPath] => true
            });
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
                    . '/app/*/app/etc/config.php' . PHP_EOL . '/app/*/app/etc/env.php',
                AppError::WARN_WRONG_OPCACHE_CONFIG
            )
            ->willReturn($this->createMock(ResultError::class));

        $this->assertInstanceOf(
            ResultError::class,
            $this->opcacheExcludePaths->validate()
        );
    }
}
