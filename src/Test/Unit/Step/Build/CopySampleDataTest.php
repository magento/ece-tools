<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Build;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Step\Build\CopySampleData;
use Magento\MagentoCloud\Step\StepException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class CopySampleDataTest extends TestCase
{
    /**
     * @var CopySampleData
     */
    private $step;

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
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->fileMock = $this->getMockBuilder(File::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->directoryListMock = $this->getMockBuilder(DirectoryList::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->directoryListMock->expects($this->any())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');

        $this->step = new CopySampleData(
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
            ->with('magento_root/vendor/magento/sample-data-media')
            ->willReturn(true);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Sample data media found. Marshalling to pub/media.');
        $this->fileMock->expects($this->once())
            ->method('copyDirectory')
            ->with('magento_root/vendor/magento/sample-data-media', 'magento_root/pub/media');

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteNoSampleData(): void
    {
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with('magento_root/vendor/magento/sample-data-media')
            ->willReturn(false);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Sample data media was not found. Skipping.');
        $this->fileMock->expects($this->never())
            ->method('copyDirectory');

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithException(): void
    {
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with('magento_root/vendor/magento/sample-data-media')
            ->willReturn(true);
        $this->fileMock->expects($this->once())
            ->method('copyDirectory')
            ->willThrowException(new FileSystemException('some error'));

        $this->expectExceptionCode(Error::BUILD_FAILED_COPY_SAMPLE_DATA);
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('some error');

        $this->step->execute();
    }
}
