<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Docker\Config\Environment;

use Magento\MagentoCloud\Docker\Config\Environment\Reader;
use Magento\MagentoCloud\Docker\ConfigurationMismatchException;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

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
     * @var DirectoryList|MockObject
     */
    private $directoryListMock;

    /**
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->fileMock = $this->createMock(File::class);

        $this->reader = new Reader($this->directoryListMock, $this->fileMock);
    }

    /**
     * @throws ConfigurationMismatchException
     */
    public function testExecute()
    {
        $this->directoryListMock->method('getDockerRoot')
            ->willReturn('docker_root');
        $this->fileMock->expects($this->exactly(2))
            ->method('isExists')
            ->with('docker_root/config.php')
            ->willReturn(true);
        $this->fileMock->expects($this->once())
            ->method('requireFile')
            ->willReturn([
                'MAGENTO_CLOUD_VARIABLES' => base64_encode(json_encode(
                    [
                        'ADMIN_EMAIL' => 'test2@email.com',
                        'ADMIN_USERNAME' => 'admin2',
                        'SCD_COMPRESSION_LEVEL' => '0',
                        'MIN_LOGGING_LEVEL' => 'debug',
                    ]
                )),
            ]);

        $this->reader->read();
    }

    /**
     * @throws ConfigurationMismatchException
     */
    public function testExecuteUsingDist()
    {
        $this->directoryListMock->method('getDockerRoot')
            ->willReturn('docker_root');
        $this->fileMock->expects($this->exactly(2))
            ->method('isExists')
            ->willReturnMap([
                ['docker_root/config.php', false],
                ['docker_root/config.php.dist', true],
            ]);
        $this->fileMock->expects($this->once())
            ->method('requireFile')
            ->willReturn([
                'MAGENTO_CLOUD_VARIABLES' => base64_encode(json_encode(
                    [
                        'ADMIN_EMAIL' => 'test2@email.com',
                        'ADMIN_USERNAME' => 'admin2',
                        'SCD_COMPRESSION_LEVEL' => '0',
                        'MIN_LOGGING_LEVEL' => 'debug',
                    ]
                )),
            ]);

        $this->reader->read();
    }

    /**
     * @expectedException \Magento\MagentoCloud\Docker\ConfigurationMismatchException
     * @expectedExceptionMessage Source file docker_root/config.php.dist does not exists
     * @throws ConfigurationMismatchException
     */
    public function testExecuteNoSource()
    {
        $this->directoryListMock->method('getDockerRoot')
            ->willReturn('docker_root');
        $this->fileMock->expects($this->exactly(2))
            ->method('isExists')
            ->willReturnMap([
                ['docker_root/config.php', false],
                ['docker_root/config.php.dist', false],
            ]);

        $this->reader->read();
    }
}
