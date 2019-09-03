<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Filesystem\Flag;

use Magento\MagentoCloud\Docker\ConfigurationMismatchException;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Filesystem\Flag\Manager;
use Magento\MagentoCloud\Filesystem\Flag\Pool;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
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
    private $flagPool;

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
    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->fileMock = $this->createMock(File::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->flagPool = $this->createMock(Pool::class);

        $this->directoryListMock->method('getMagentoRoot')
            ->willReturn($this->magentoRoot);
        $this->directoryListMock->method('getPath')
            ->willReturn($this->backupRoot);

        $this->manager = new Manager(
            $this->loggerMock,
            $this->fileMock,
            $this->flagPool,
            $this->directoryListMock
        );

        parent::setUp();
    }

    /**
     * @throws ConfigurationMismatchException
     */
    public function testGetFlag(): void
    {
        $this->flagPool->expects($this->once())
            ->method('get')
            ->with('some_flag')
            ->willReturn('flag/path');

        $this->assertEquals(
            'flag/path',
            $this->manager->getFlagPath('some_flag')
        );
    }

    /**
     * @throws ConfigurationMismatchException
     *
     * @expectedException \Magento\MagentoCloud\Docker\ConfigurationMismatchException
     * @expectedExceptionMessage Flag with key some_flag is not registered in flagPool
     */
    public function testGetFlagWithException(): void
    {
        $this->flagPool->expects($this->once())
            ->method('get')
            ->with('some_flag')
            ->willReturn(null);

        $this->manager->getFlagPath('some_flag');
    }

    public function flagDataProvider(): array
    {
        return [
            ['key' => 'key1', 'path' => '.some_flag', 'flagState' => true],
            ['key' => 'key2', 'path' => 'what/the/what/.some_flag', 'flagState' => false],
        ];
    }

    /**
     * @param string $key
     * @param string $path
     * @param bool $flagState
     * @dataProvider flagDataProvider
     *
     * @throws ConfigurationMismatchException
     */
    public function testExists(string $key, string $path, bool $flagState)
    {
        $this->flagPool->expects($this->once())
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
     * @param string $key
     * @param string $path
     * @param bool $flagState
     * @dataProvider flagDataProvider
     *
     * @throws ConfigurationMismatchException
     */
    public function testSet(string $key, string $path, bool $flagState): void
    {
        $this->flagPool->expects($this->once())
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
     * @param string $key
     * @param string $path
     * @param bool $flagState
     * @param bool $deleteResult
     * @param array $logs
     * @param bool $result
     * @dataProvider deleteDataProvider
     *
     * @throws ConfigurationMismatchException
     */
    public function testDelete(
        string $key,
        string $path,
        bool $flagState,
        bool $deleteResult,
        array $logs,
        bool $result
    ): void {
        $this->flagPool->expects($this->any())
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
                ->withConsecutive($logs);
        } else {
            $this->loggerMock->expects($this->exactly(count($logs)))
                ->method('debug')
                ->withConsecutive($logs);
        }

        $this->assertSame(
            $result,
            $this->manager->delete($key)
        );
    }

    public function deleteDataProvider(): array
    {
        return [
            [
                'key' => '.some_flag1',
                'path' => 'path/to/.some_flag1',
                'flagState' => true,
                'deleteResult' => true,
                'logs' => ['Deleting flag: path/to/.some_flag1'],
                'result' => true,
            ],
            [
                'key' => '.some_flag2',
                'path' => 'path/to/.some_flag2',
                'flagState' => false,
                'deleteResult' => false,
                'logs' => ['Flag path/to/.some_flag2 has already been deleted.'],
                'result' => true,
            ],
            [
                'key' => '.some_flag3',
                'path' => 'path/to/.some_flag3',
                'flagState' => true,
                'deleteResult' => false,
                'logs' => [],
                'result' => false,
            ],
        ];
    }

    /**
     * @throws ConfigurationMismatchException
     */
    public function testExistsWithFileSystemException(): void
    {
        $path = 'path/that/doesnt/exist';
        $this->flagPool->expects($this->any())
            ->method('get')
            ->with('some_key')
            ->willReturn($path);
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($this->magentoRoot);
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->willThrowException(new FileSystemException('Error occurred during execution'));
        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with('Error occurred during execution');

        $this->assertFalse($this->manager->exists('some_key'));
    }

    /**
     * @throws ConfigurationMismatchException
     */
    public function testSetWithFileSystemException(): void
    {
        $path = 'path/that/doesnt/exist';
        $this->flagPool->expects($this->any())
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
