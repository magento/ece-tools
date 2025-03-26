<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\App;

use Magento\MagentoCloud\App\ErrorInfo;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class ErrorInfoTest extends TestCase
{
    /**
     * @var ErrorInfo
     */
    private $errorInfo;

    /**
     * @var FileList|MockObject
     */
    private $fileListMock;

    /**
     * @var File|MockObject
     */
    private $fileMock;

    protected function setUp(): void
    {
        $this->fileListMock = $this->createMock(FileList::class);
        $this->fileMock = $this->createMock(File::class);

        $this->errorInfo = new ErrorInfo($this->fileMock, $this->fileListMock);
    }

    /**
     * @throws \Magento\MagentoCloud\Filesystem\FileSystemException
     * @dataProvider getErrorDataProvider
     */
    public function testGetError(int $errorCode, array $expected)
    {
        $filePath = __DIR__ . '/_file/schema.error.yaml';
        $this->fileListMock->expects($this->once())
            ->method('getErrorSchema')
            ->willReturn($filePath);
        $this->fileMock->expects($this->once())
            ->method('fileGetContents')
            ->with($filePath)
            ->willReturn(file_get_contents($filePath));

        $this->assertEquals($expected, $this->errorInfo->get($errorCode));
    }

    /**
     * @return array
     */
    public function getErrorDataProvider(): array
    {
        return [
            [
                12,
                [],
            ],
            [
                2,
                [
                    'title' => 'Critical error',
                    'suggestion' => 'Critical error suggestion',
                    'stage' => 'build',
                    'type' => 'critical',
                ]
            ],
            [
                1001,
                [
                    'title' => 'Warning error',
                    'suggestion' => 'Warning error suggestion',
                    'stage' => 'build',
                    'type' => 'warning',
                ]
            ]
        ];
    }
}
