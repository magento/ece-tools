<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Build;

use Magento\MagentoCloud\Config\Build;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Process\Build\CompileDi;
use Magento\MagentoCloud\Shell\ShellInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class CompileDiTest extends TestCase
{
    /**
     * @var CompileDi
     */
    private $process;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerMock;

    /**
     * @var ShellInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shellMock;

    /**
     * @var Build|\PHPUnit_Framework_MockObject_MockObject
     */
    private $buildConfigMock;

    /**
     * @var DirectoryList|\PHPUnit_Framework_MockObject_MockObject
     */
    private $directoryListMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->shellMock = $this->getMockBuilder(ShellInterface::class)
            ->getMockForAbstractClass();
        $this->buildConfigMock = $this->getMockBuilder(Build::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->directoryListMock = $this->getMockBuilder(DirectoryList::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->directoryListMock->expects($this->any())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');

        $this->process = new CompileDi(
            $this->loggerMock,
            $this->shellMock,
            $this->buildConfigMock,
            $this->directoryListMock
        );
    }

    public function testExecute()
    {
        $this->buildConfigMock->expects($this->once())
            ->method('getVerbosityLevel')
            ->willReturn('-vvv');
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Running DI compilation');
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('php ./bin/magento setup:di:compile -vvv');

        $this->process->execute();
    }
}
