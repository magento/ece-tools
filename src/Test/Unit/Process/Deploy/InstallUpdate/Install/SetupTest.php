<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\Install;

use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\Install\Setup;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\Install\Setup\InstallCommandFactory;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class SetupTest extends TestCase
{
    /**
     * @var Setup
     */
    private $process;

    /**
     * @var ShellInterface|MockObject
     */
    private $shellMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var FileList|MockObject
     */
    private $fileListMock;

    /**
     * @var InstallCommandFactory|MockObject
     */
    private $installCommandFactoryMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->shellMock = $this->getMockForAbstractClass(ShellInterface::class);
        $this->fileListMock = $this->createMock(FileList::class);
        $this->installCommandFactoryMock = $this->createMock(InstallCommandFactory::class);

        $this->process = new Setup(
            $this->loggerMock,
            $this->shellMock,
            $this->fileListMock,
            $this->installCommandFactoryMock
        );
    }

    public function testExecute()
    {
        $installUpgradeLog = '/tmp/log.log';

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Installing Magento.');
        $this->fileListMock->expects($this->once())
            ->method('getInstallUpgradeLog')
            ->willReturn($installUpgradeLog);
        $this->installCommandFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn('magento install command');

        $this->shellMock->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                ['echo \'Installation time: \'$(date) | tee -a ' . $installUpgradeLog],
                ['/bin/bash -c "set -o pipefail; magento install command | tee -a /tmp/log.log"']
            );

        $this->process->execute();
    }

    /**
     * @expectedException \Magento\MagentoCloud\Process\ProcessException
     * @expectedExceptionMessage script error
     */
    public function testExecuteWithException()
    {
        $installUpgradeLog = '/tmp/log.log';

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Installing Magento.');
        $this->fileListMock->expects($this->once())
            ->method('getInstallUpgradeLog')
            ->willReturn($installUpgradeLog);
        $this->installCommandFactoryMock->expects($this->never())
            ->method('create');
        $this->shellMock->expects($this->any())
            ->method('execute')
            ->willThrowException(new ShellException('script error'));

        $this->process->execute();
    }
}
