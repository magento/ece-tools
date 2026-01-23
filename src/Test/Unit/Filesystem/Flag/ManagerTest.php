<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Filesystem\Flag;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\Flag\ConfigurationMismatchException;
use Magento\MagentoCloud\Filesystem\Flag\Manager;
use Magento\MagentoCloud\Filesystem\Flag\Pool;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
#[AllowMockObjectsWithoutExpectations]
class ManagerTest extends TestCase
{
    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @var DirectoryList|MockObject
     */
    private $directoryListMock;

    /**
     * @var Pool|MockObject
     */
    private $pool;

    /**
     * @var Manager
     */
    private $manager;

    /**
     * @var string
     */
    private $magentoRoot = 'magento_root';

    /**
     * @var string
     */
    private $backupRoot = 'magento_root/init';

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->fileMock = $this->createMock(File::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->pool = $this->createMock(Pool::class);

        $this->directoryListMock->method('getMagentoRoot')
            ->willReturn($this->magentoRoot);
        $this->directoryListMock->method('getPath')
            ->willReturn($this->backupRoot);

        $this->manager = new Manager(
            $this->loggerMock,
            $this->fileMock,
            $this->pool,
            $this->directoryListMock
        );

        parent::setUp();
    }

    /**
     * Test get flag method.
     *
     * @return void
     * @throws ConfigurationMismatchException
     */
    public function testGetFlag(): void
    {
        $this->pool->expects($this->once())
            ->method('get')
            ->with('some_flag')
            ->willReturn('flag/path');

        $this->assertEquals(
            'flag/path',
            $this->manager->getFlagPath('some_flag')
        );
    }

    /**
     * Test get flag with exception.
     *
     * @return void
     * @throws ConfigurationMismatchException
     */
    public function testGetFlagWithException(): void
    {
        $this->expectException(ConfigurationMismatchException::class);
        $this->expectExceptionMessage('Flag with key some_flag is not registered in pool');

        $this->pool->expects($this->once())
            ->method('get')
            ->with('some_flag')
            ->willReturn(null);

        $this->manager->getFlagPath('some_flag');
    }

    /**
     * Data provider for flag method.
     *
     * @return array
     */
    public static function flagDataProvider(): array
    {
        return [
            [
                'key'          => 'key1',
                'path'         => '.some_flag',
                'flagState'    => true,
            ],
            [
                'key'          => 'key2',
                'path'         => 'what/the/what/.some_flag',
                'flagState'    => false,
            ],
        ];
    }

    /**
     * Test exists method.
     *
     * @param string $key
     * @param string $path
     * @param bool $flagState
     * @dataProvider flagDataProvider
     * @return void
     * @throws ConfigurationMismatchException
     */
    #[DataProvider('flagDataProvider')]
    public function testExists(string $key, string $path, bool $flagState): void
    {
        $this->pool->expects($this->once())
            ->method('get')
            ->with($key)
            ->willReturn($path);
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with("magento_root/$path")
            ->willReturn($flagState);

        $this->assertSame($flagState, $this->manager->exists($key));
    }

    /**
     * Test set method.
     *
     * @param string $key
     * @param string $path
     * @param bool $flagState
     * @dataProvider flagDataProvider
     * @return void
     * @throws ConfigurationMismatchException
     */
    #[DataProvider('flagDataProvider')]
    public function testSet(string $key, string $path, bool $flagState): void
    {
        $this->pool->expects($this->once())
            ->method('get')
            ->with($key)
            ->willReturn($path);
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->expects($this->once())
            ->method('touch')
            ->with("magento_root/$path")
            ->willReturn($flagState);
        if ($flagState) {
            $this->loggerMock->expects($this->once())
                ->method('info')
                ->with('Set flag: ' . $path);
        }

        $this->assertSame(
            $flagState,
            $this->manager->set($key)
        );
    }

    /**
     * Test delete method.
     *
     * @param string $key
     * @param string $path
     * @param bool $flagState
     * @param bool $deleteResult
     * @param array $logs
     * @param bool $result
     * @dataProvider deleteDataProvider
     * @return void
     * @throws ConfigurationMismatchException
     */
    #[DataProvider('deleteDataProvider')]
    public function testDelete(
        string $key,
        string $path,
        bool $flagState,
        bool $deleteResult,
        array $logs,
        bool $result
    ): void {
        $this->pool->expects($this->any())
            ->method('get')
            ->with($key)
            ->willReturn($path);
        $this->directoryListMock->expects($this->any())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with('magento_root/' . $path)
            ->willReturn($flagState);
        if ($flagState) {
            $this->fileMock->expects($this->once())
                ->method('deleteFile')
                ->with('magento_root/' . $path)
                ->willReturn($deleteResult);
            $this->loggerMock->expects($this->exactly(count($logs)))
                ->method('info')
                // withConsecutive() alternative.
                ->willReturnCallback(function ($logs) {
                    if (!empty($args)) {
                        return null;
                    }
                });
        } else {
            $this->loggerMock->expects($this->exactly(count($logs)))
                ->method('debug')
                ->willReturnCallback(function ($logs) {
                    if (!empty($args)) {
                        return null;
                    }
                });
        }

        $this->assertSame(
            $result,
            $this->manager->delete($key)
        );
    }

    /**
     * Data provider for delete method.
     *
     * @return array
     */
    public static function deleteDataProvider(): array
    {
        return [
            [
                'key'          => '.some_flag1',
                'path'         => 'path/to/.some_flag1',
                'flagState'    => true,
                'deleteResult' => true,
                'logs'         => ['Deleting flag: path/to/.some_flag1'],
                'result'       => true,
            ],
            [
                'key'          => '.some_flag2',
                'path'         => 'path/to/.some_flag2',
                'flagState'    => false,
                'deleteResult' => false,
                'logs'         => ['Flag path/to/.some_flag2 has already been deleted.'],
                'result'       => true,
            ],
            [
                'key'          => '.some_flag3',
                'path'         => 'path/to/.some_flag3',
                'flagState'    => true,
                'deleteResult' => false,
                'logs'         => [],
                'result'       => false,
            ],
        ];
    }

    /**
     * Test set with file system exception.
     *
     * @return void
     * @throws ConfigurationMismatchException
     */
    public function testSetWithFileSystemException(): void
    {
        $path = 'path/that/doesnt/exist';
        $this->pool->expects($this->any())
            ->method('get')
            ->with('some_key')
            ->willReturn($path);
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->expects($this->once())
            ->method('touch')
            ->willReturn(false);
        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with('Cannot create flag some_key');

        $this->assertFalse($this->manager->set('some_key'));
    }
}
