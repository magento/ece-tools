<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Build;

use Magento\MagentoCloud\Step\Build\CopyPubStatic;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Step\StepException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritDoc
 */
class CopyPubStaticTest extends TestCase
{
    /**
     * @var CopyPubStatic
     */
    private $step;

    /**
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var DirectoryList|MockObject
     */
    private $directoryListMock;

    /**
     * @var FileList|MockObject
     */
    private $fileListMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->fileMock = $this->createMock(File::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->fileListMock = $this->createMock(FileList::class);

        $this->directoryListMock->method('getMagentoRoot')
            ->willReturn('magento_root');

        $this->step = new CopyPubStatic(
            $this->loggerMock,
            $this->fileMock,
            $this->directoryListMock,
            $this->fileListMock
        );
    }

    /**
     * @throws StepException
     */
    public function testExecute(): void
    {
        $frontStaticDistPath = 'path/dist/front-static.php.dist';
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileListMock->expects($this->once())
            ->method('getFrontStaticDist')
            ->willReturn($frontStaticDistPath);
        $this->fileMock->expects($this->once())
            ->method('copy')
            ->with(
                $frontStaticDistPath,
                'magento_root/pub/front-static.php'
            );
        $this->loggerMock->method('info')
            ->with('File "front-static.php" was copied');

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteNotFound(): void
    {
        $frontStaticDistPath = 'path/dist/front-static.php.dist';
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileListMock->expects($this->once())
            ->method('getFrontStaticDist')
            ->willReturn($frontStaticDistPath);
        $this->fileMock->expects($this->once())
            ->method('copy')
            ->with(
                $frontStaticDistPath,
                'magento_root/pub/front-static.php'
            );
        $this->loggerMock->method('info')
            ->with('File "front-static.php" was copied');

        $this->step->execute();
    }
}
