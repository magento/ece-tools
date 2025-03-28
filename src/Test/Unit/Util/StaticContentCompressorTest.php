<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Util;

use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Util\StaticContentCompressor;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Shell\UtilityManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for static content compression.
 */
class StaticContentCompressorTest extends TestCase
{
    /**
     * @var StaticContentCompressor
     */
    private $staticContentCompressor;

    /**
     * @var DirectoryList|MockObject
     */
    private $directoryListMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var ShellInterface|MockObject
     */
    private $shellMock;

    /**
     * @var UtilityManager|MockObject
     */
    private $utilityManagerMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->shellMock = $this->getMockForAbstractClass(ShellInterface::class);
        $this->utilityManagerMock = $this->createMock(UtilityManager::class);

        $this->staticContentCompressor = new StaticContentCompressor(
            $this->directoryListMock,
            $this->loggerMock,
            $this->shellMock,
            $this->utilityManagerMock
        );
    }

    /**
     * @param int $compressionLevel
     * @param int $compressionTimeout
     * @dataProvider compressionDataProvider
     */
    public function testCompression(int $compressionLevel, int $compressionTimeout)
    {
        $directoryListDirStatic = 'this/is/a/test/static/directory';
        $timeoutCommand = '/usr/bin/timeout';
        $bash = '/bin/bash';

        $this->directoryListMock
            ->expects($this->once())
            ->method('getPath')
            ->willReturn($directoryListDirStatic);

        $expectedCommand = sprintf(
            '%s -k 30 %s %s -c %s',
            $timeoutCommand,
            $compressionTimeout,
            $bash,
            escapeshellarg(
                sprintf(
                    "find %s -type d -name %s -prune -o -type f -size +300c"
                    . " '(' -name '*.js' -or -name '*.css' -or -name '*.svg'"
                    . " -or -name '*.html' -or -name '*.htm' ')' -print0"
                    . " | xargs -0 -n100 -P16 gzip -q --keep -%d",
                    escapeshellarg($directoryListDirStatic),
                    escapeshellarg(File::DELETING_PREFIX . '*'),
                    $compressionLevel
                )
            )
        );

        $this->shellMock
            ->expects($this->once())
            ->method('execute')
            ->with($expectedCommand);
        $this->utilityManagerMock->method('get')
            ->willReturnMap([
                [UtilityManager::UTILITY_TIMEOUT, $timeoutCommand],
                [UtilityManager::UTILITY_SHELL, $bash],
            ]);
        $this->loggerMock->expects($this->once())
            ->method('info');

        $this->staticContentCompressor->process($compressionLevel, $compressionTimeout, '-v');
    }

    /**
     * @return array
     */
    public function compressionDataProvider(): array
    {
        return [
            [4, 500],
            [9, 100000],
        ];
    }

    public function testCompressionDisabled()
    {
        $this->shellMock
            ->expects($this->never())
            ->method('execute');
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Static content compression was disabled.');

        $this->staticContentCompressor->process(0);
    }

    public function testUtilityNotFound()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Utility was not found');

        $this->shellMock
            ->expects($this->never())
            ->method('execute');
        $this->utilityManagerMock->expects($this->once())
            ->method('get')
            ->with(UtilityManager::UTILITY_TIMEOUT)
            ->willThrowException(new \RuntimeException('Utility was not found.'));

        $this->staticContentCompressor->process(1);
    }
}
