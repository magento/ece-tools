<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\Update;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FlagFile\RegenerateFlag;
use Magento\MagentoCloud\Filesystem\FlagFileInterface;
use Magento\MagentoCloud\Filesystem\FlagFilePool;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\Update\Setup;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Filesystem\FileList;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

class SetupTest extends TestCase
{
    /**
     * @var Setup
     */
    private $process;

    /**
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var ShellInterface|Mock
     */
    private $shellMock;

    /**
     * @var FlagFilePool|Mock
     */
    private $flagFilePoolMock;

    /**
     * @var FileList|Mock
     */
    private $fileListMock;

    /**
     * @var DirectoryList|Mock
     */
    private $directoryListMock;

    /**
     * @var FlagFileInterface|Mock
     */
    private $flagMock;

    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->shellMock = $this->getMockForAbstractClass(ShellInterface::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->fileListMock = $this->createMock(FileList::class);
        $this->flagFilePoolMock = $this->createMock(FlagFilePool::class);
        $this->flagMock = $this->getMockBuilder(FlagFileInterface::class)
            ->getMockForAbstractClass();

        $this->process = new Setup(
            $this->loggerMock,
            $this->environmentMock,
            $this->shellMock,
            $this->directoryListMock,
            $this->fileListMock,
            $this->flagFilePoolMock
        );
    }

    public function testExecute()
    {
        $installUpgradeLog = '/tmp/log.log';

        $this->directoryListMock->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->environmentMock->expects($this->once())
            ->method('getVerbosityLevel')
            ->willReturn('-v');
        $this->fileListMock->expects($this->once())
            ->method('getInstallUpgradeLog')
            ->willReturn($installUpgradeLog);
//        $this->fileMock->expects($this->exactly(2))
//            ->method('isExists')
//            ->with('magento_root/' . RegenerateFlag::KEY)
//            ->willReturn(true);
//        $this->fileMock->expects($this->exactly(2))
//            ->method('deleteFile')
//            ->with('magento_root/' . RegenerateFlag::KEY);

        $this->flagFilePoolMock->expects($this->once())
            ->method('getFlag')
            ->with('regenerate')
            ->willReturn($this->flagMock);
        $this->flagMock->expects($this->exactly(2))
            ->method('delete');
        $this->environmentMock->expects($this->once())
            ->method('getVerbosityLevel')
            ->willReturn('-v');
        $this->shellMock->expects($this->exactly(3))
            ->method('execute')
            ->withConsecutive(
                ['php ./bin/magento maintenance:enable -v'],
                [
                    '/bin/bash -c "set -o pipefail; php ./bin/magento setup:upgrade --keep-generated -n -v | tee -a '
                    . $installUpgradeLog . '"'
                ],
                ['php ./bin/magento maintenance:disable -v']
            );
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Running setup upgrade.');
        $this->loggerMock->expects($this->exactly(2))
            ->method('notice')
            ->withConsecutive(
                ['Enabling Maintenance mode.'],
                ['Maintenance mode is disabled.']
            );

        $this->process->execute();
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Error during command execution
     */
    public function testExecuteWithException()
    {
        $this->flagFilePoolMock->expects($this->once())
            ->method('getFlag')
            ->with('regenerate')
            ->willReturn($this->flagMock);
        $this->flagMock->expects($this->once())
            ->method('delete');
        $this->shellMock->expects($this->at(0))
            ->method('execute')
            ->willThrowException(new \RuntimeException('Error during command execution'));

        $this->process->execute();
    }
}
