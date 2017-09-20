<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\Update;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\Update\Setup;
use Magento\MagentoCloud\Shell\ShellInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

class SetupTest extends TestCase
{
    /**
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var File|Mock
     */
    private $fileMock;

    /**
     * @var ShellInterface|Mock
     */
    private $shellMock;

    /**
     * @var Setup
     */
    private $process;

    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->fileMock = $this->createMock(File::class);
        $this->shellMock = $this->getMockForAbstractClass(ShellInterface::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);

        $this->process = new Setup(
            $this->loggerMock,
            $this->environmentMock,
            $this->shellMock,
            $this->fileMock
        );
    }

    public function testExecute()
    {
        $this->environmentMock->expects($this->once())
            ->method('getVerbosityLevel')
            ->willReturn('-v');
        $this->fileMock->expects($this->exactly(2))
            ->method('isExists')
            ->with(Environment::REGENERATE_FLAG)
            ->willReturn(true);
        $this->fileMock->expects($this->exactly(2))
            ->method('deleteFile')
            ->with(Environment::REGENERATE_FLAG);
        $this->shellMock->expects($this->exactly(3))
            ->method('execute')
            ->withConsecutive(
                ['php ./bin/magento maintenance:enable -v'],
                ['php ./bin/magento setup:upgrade --keep-generated -n -v'],
                ['php ./bin/magento maintenance:disable -v']
            );
        $this->loggerMock->expects($this->exactly(3))
            ->method('info')
            ->withConsecutive(
                ['Removing .regenerate flag'],
                ['Running setup upgrade.'],
                ['Removing .regenerate flag']
            );
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
        $this->shellMock->expects($this->at(0))
            ->method('execute')
            ->willThrowException(new \RuntimeException('Error during command execution'));

        $this->process->execute();
    }
}
