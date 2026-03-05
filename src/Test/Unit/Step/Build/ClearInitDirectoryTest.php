<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Build;

use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Step\Build\ClearInitDirectory;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Step\StepException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class ClearInitDirectoryTest extends TestCase
{
    /**
     * @var ClearInitDirectory
     */
    private $step;

    /**
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @var DirectoryList|MockObject
     */
    private $directoryListMock;

    /**
     * @var FileList|MockObject
     */
    private $fileListMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->fileMock = $this->createMock(File::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->fileListMock = $this->createMock(FileList::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->step = new ClearInitDirectory(
            $this->fileMock,
            $this->directoryListMock,
            $this->fileListMock,
            $this->loggerMock
        );
    }

    /**
     * Test execute method.
     *
     * @param bool $isExists
     * @param int $clearDirectory
     * @param int $deleteFile
     * @dataProvider executeDataProvider
     * @return void
     * @throws StepException
     * @throws \ReflectionException
     */
    #[DataProvider('executeDataProvider')]
    public function testExecute($isExists, $clearDirectory, $deleteFile): void
    {
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Clearing temporary directory.');
        $this->directoryListMock->expects($this->once())
            ->method('getInit')
            ->willReturn('magento_root/init');
        $this->fileListMock->expects($this->once())
            ->method('getEnv')
            ->willReturn('magento_root/app/etc/env.php');
        $this->fileMock->expects($this->exactly(2))
            ->method('isExists')
            ->willReturnMap([
                ['magento_root/init', $isExists],
                ['magento_root/app/etc/env.php', $isExists]
            ]);
        $this->fileMock->expects($this->exactly($clearDirectory))
            ->method('clearDirectory')
            ->with('magento_root/init')
            ->willReturn(true);
        $this->fileMock->expects($this->exactly($deleteFile))
            ->method('deleteFile')
            ->with('magento_root/app/etc/env.php')
            ->willReturn(true);

        $this->step->execute();
    }

    /**
     * Data provider for execute method.
     *
     * @return array
     */
    public static function executeDataProvider(): array
    {
        return [
            [
                'isExists'       => true,
                'clearDirectory' => 1,
                'deleteFile'     => 1,
            ],
            [
                'isExists'       => false,
                'clearDirectory' => 0,
                'deleteFile'     => 0,
            ],
        ];
    }
}
