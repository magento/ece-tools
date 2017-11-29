<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\Update;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FlagFileInterface;
use Magento\MagentoCloud\Filesystem\FlagFilePool;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\Update\Setup;
use Magento\MagentoCloud\Shell\ShellInterface;
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
     * @var FlagFileInterface|Mock
     */
    private $flagMock;

    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->shellMock = $this->getMockForAbstractClass(ShellInterface::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->flagFilePoolMock = $this->createMock(FlagFilePool::class);
        $this->flagMock = $this->getMockBuilder(FlagFileInterface::class)
            ->getMockForAbstractClass();

        $this->process = new Setup(
            $this->loggerMock,
            $this->environmentMock,
            $this->shellMock,
            $this->flagFilePoolMock
        );
    }

    public function testExecute()
    {

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
                ['php ./bin/magento setup:upgrade --keep-generated -n -v'],
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
