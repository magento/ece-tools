<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Build;

use Magento\MagentoCloud\Command\CopyPubStatic;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
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
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->fileMock = $this->createMock(File::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);

        $this->directoryListMock->method('getMagentoRoot')
            ->willReturn('magento_root');

        $this->step = new CopyPubStatic(
            $this->loggerMock,
            $this->fileMock,
            $this->directoryListMock
        );
    }

    /**
     * @throws StepException
     */
    public function testExecute(): void
    {
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with('magento_root/pub/static.php')
            ->willReturn(true);
        $this->fileMock->expects($this->once())
            ->method('copy')
            ->with(
                'magento_root/pub/static.php',
                'magento_root/pub/front-static.php'
            );
        $this->loggerMock->method('info')
            ->withConsecutive(
                ['File "static.php" was copied']
            );

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteNotFound(): void
    {
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with('magento_root/pub/static.php')
            ->willReturn(false);
        $this->fileMock->expects($this->never())
            ->method('copy');
        $this->loggerMock->method('notice')
            ->withConsecutive(
                ['File "static.php" was not found']
            );

        $this->step->execute();
    }
}
