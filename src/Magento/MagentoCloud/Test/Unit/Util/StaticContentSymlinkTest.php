<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Util;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Util\StaticContentSymlink;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

class StaticContentSymlinkTest extends TestCase
{
    /**
     * @var ShellInterface|Mock
     */
    private $shellMock;

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
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @var StaticContentSymlink
     */
    private $staticContentSymlink;

    protected function setUp()
    {
        $this->shellMock = $this->getMockBuilder(ShellInterface::class)
            ->getMockForAbstractClass();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->fileMock = $this->createMock(File::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->environmentMock = $this->createMock(Environment::class);

        $this->staticContentSymlink = new StaticContentSymlink(
            $this->loggerMock,
            $this->shellMock,
            $this->environmentMock,
            $this->fileMock,
            $this->directoryListMock
        );
    }

    public function testCreate()
    {
        $root = __DIR__ . '/_files';
        $staticContentLocation = $root . '/pub/static';
        $buildDir = $root . '/init/pub/static';

        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($root);
        $this->fileMock->expects($this->exactly(2))
            ->method('getRealPath')
            ->withConsecutive(
                [$staticContentLocation],
                [$buildDir]
            )
            ->willReturnOnConsecutiveCalls(
                $staticContentLocation,
                $buildDir
            );
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($buildDir)
            ->willReturn(true);

        $this->fileMock->expects($this->exactly(2))
            ->method('symlink')
            ->withConsecutive(
                [$buildDir . '/config.php'],
                [$buildDir . '/frontend']
            )
            ->willReturn(true);
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                [sprintf('Create symlink %s/config.php => %s/config.php', $staticContentLocation, $buildDir)],
                [sprintf('Create symlink %s/frontend => %s/frontend', $staticContentLocation, $buildDir)]
            )
            ->willReturn(true);

        $this->staticContentSymlink->create();
    }

    public function testCreateWithSymlinkException()
    {
        $root = __DIR__ . '/_files';
        $staticContentLocation = $root . '/pub/static';
        $buildDir = $root . '/init/pub/static';

        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($root);
        $this->fileMock->expects($this->exactly(2))
            ->method('getRealPath')
            ->withConsecutive(
                [$staticContentLocation],
                [$buildDir]
            )
            ->willReturnOnConsecutiveCalls(
                $staticContentLocation,
                $buildDir
            );
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($buildDir)
            ->willReturn(true);

        $this->fileMock->expects($this->exactly(2))
            ->method('symlink')
            ->withConsecutive(
                [$buildDir . '/config.php'],
                [$buildDir . '/frontend']
            )
            ->willThrowException(new FileSystemException('Can\'t create symlink'));
        $this->loggerMock->expects($this->never())
            ->method('info');
        $this->loggerMock->expects($this->exactly(2))
            ->method('error')
            ->withConsecutive(
                ['Can\'t create symlink'],
                ['Can\'t create symlink']
            );

        $this->staticContentSymlink->create();
    }

    public function testCreateBuildDirNotExists()
    {
        $root = __DIR__ . '/_files';
        $staticContentLocation = $root . '/pub/static';
        $buildDir = $root . '/init/pub/static';

        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($root);
        $this->fileMock->expects($this->exactly(2))
            ->method('getRealPath')
            ->withConsecutive(
                [$staticContentLocation],
                [$buildDir]
            )
            ->willReturnOnConsecutiveCalls(
                $staticContentLocation,
                $buildDir
            );
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($buildDir)
            ->willReturn(false);

        $this->fileMock->expects($this->never())
            ->method('symlink');
        $this->loggerMock->expects($this->never())
            ->method('info');

        $this->staticContentSymlink->create();
    }
}
