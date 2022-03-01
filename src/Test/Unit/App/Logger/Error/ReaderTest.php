<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\App\Logger\Error;

use Codeception\PHPUnit\TestCase;
use Magento\MagentoCloud\App\Logger\Error\Reader;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @inheritDoc
 */
class ReaderTest extends TestCase
{
    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var FileList|MockObject
     */
    private $fileListMock;

    protected function setUp(): void
    {
        $this->fileListMock = $this->createMock(FileList::class);

        $this->reader = new Reader($this->fileListMock);
    }

    public function testRead()
    {
        $this->fileListMock->expects($this->once())
            ->method('getCloudErrorLog')
            ->willReturn(__DIR__ . '/_file/cloud.error.log');

        $this->assertEquals(
            [
                2007 => [
                    'errorCode' => 2007,
                    'stage' => 'deploy',
                    'step' => 'validate-config',
                    'suggestion' => 'warning suggestion',
                    'title' => 'warning message',
                    'type' => 'warning',
                ],
                109 => [
                    'errorCode' => 109,
                    'stage' => 'deploy',
                    'step' => 'validate-config',
                    'suggestion' => 'critical suggestion 1',
                    'title' => 'critical message 1',
                    'type' => 'critical',
                ],
                111 => [
                    'errorCode' => 111,
                    'stage' => 'deploy',
                    'step' => 'validate-config',
                    'suggestion' => 'critical suggestion 2',
                    'title' => 'critical message 2',
                    'type' => 'critical',
                ]
            ],
            $this->reader->read()
        );
    }

    public function testReadWithException()
    {
        $this->fileListMock->expects($this->once())
            ->method('getCloudErrorLog')
            ->willThrowException(new UndefinedPackageException('some error'));

        $this->assertEquals([], $this->reader->read());
    }
}
