<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Filesystem\FlagFile;

use JsonSchema\Exception\RuntimeException;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Filesystem\FlagFile\Base;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class BaseTest extends TestCase
{
    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var File|Mock
     */
    private $fileMock;

    /**
     * @var DirectoryList|Mock
     */
    private $directoryListMock;

    /**
     * @var Base
     */
    private $base;

    private $magentoRoot = 'magento_root';
    private $backupRoot = 'magento_root/init';

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->fileMock = $this->createMock(File::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);

        $this->directoryListMock->expects($this->any())
            ->method('getMagentoRoot')
            ->willReturn($this->magentoRoot);
        $this->directoryListMock->expects($this->any())
            ->method('getPath')
            ->willReturn($this->backupRoot);

        $this->base = new Base(
            $this->loggerMock,
            $this->fileMock,
            $this->directoryListMock
        );

        parent::setUp();
    }

    public function flagDataProvider()
    {
        return [
            ['path' => '.some_flag', 'flagState' => true],
            ['path' => 'what/the/what/.some_flag', 'flagState' => false]
        ];
    }

    /**
     * @dataProvider flagDataProvider
     */
    public function testExists($path, $flagState)
    {
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with("magento_root/$path")
            ->willReturn($flagState);

        $this->assertSame($flagState, $this->base->exists($path));
    }

    /**
     * @dataProvider flagDataProvider
     */
    public function testSet($path, $flagState)
    {
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

        $this->assertSame($flagState, $this->base->set($path));
    }

    public function deleteDataProvider()
    {
        return [
            [
                'root' => 'magento_root',
                'path' => '.some_flag',
                'flag' => 'magento_root/.some_flag',
                'flagState' => true,
                'deleteResult' => true,
                'logs' => ['Deleted flag: .some_flag'],
                'result' => true
            ],
            [
                'root' => 'magento_root',
                'path' => '.some_flag',
                'flag' => 'magento_root/.some_flag',
                'flagState' => false,
                'deleteResult' => false,
                'logs' => ['Flag already deleted: .some_flag'],
                'result' => true
            ],
            [
                'root' => 'magento_root',
                'path' => '.some_flag',
                'flag' => 'magento_root/.some_flag',
                'flagState' => true,
                'deleteResult' => false,
                'logs' => [],
                'result' => false
            ],
        ];
    }

    /**
     * @dataProvider deleteDataProvider
     */
    public function testDelete($root, $path, $flag, $flagState, $deleteResult, $logs, $result)
    {
        $this->directoryListMock->expects($this->any())
            ->method('getMagentoRoot')
            ->willReturn($root);
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($flag)
            ->willReturn($flagState);
        if ($flagState) {
            $this->fileMock->expects($this->once())
                ->method('deleteFile')
                ->with("$flag")
                ->willReturn($deleteResult);
        }
        $this->loggerMock->expects($this->exactly(count($logs)))
            ->method('info')
            ->withConsecutive($logs);

        $this->assertSame($result, $this->base->delete($path));
    }

    public function testExistsException()
    {
        $path = 'path/that/doesnt/exist';
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($this->magentoRoot);
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->willThrowException(new FileSystemException('Error occurred during execution'));
        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with('Error occurred during execution');

        $this->assertFalse($this->base->exists($path));
    }


    public function testSetException()
    {
        $path = 'path/that/doesnt/exist';
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->expects($this->once())
            ->method('touch')
            ->willThrowException(new FileSystemException('Error occurred during execution'));
        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with('Error occurred during execution');

        $this->assertFalse($this->base->set($path));
    }


    public function testDeleteException()
    {
        $root = $this->magentoRoot;
        $path = '.some_flag';
        $flag = 'magento_root/.some_flag';
        $flagState = true;

        $this->directoryListMock->expects($this->any())
            ->method('getMagentoRoot')
            ->willReturn($root);
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($flag)
            ->willReturn($flagState);
        $this->fileMock->expects($this->once())
            ->method('deleteFile')
            ->willThrowException(new FileSystemException('Error occurred during execution'));
        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with('Error occurred during execution');

        $this->assertFalse($this->base->delete($path));
    }
}
