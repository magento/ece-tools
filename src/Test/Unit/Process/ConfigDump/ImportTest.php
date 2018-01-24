<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\ConfigDump;

use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Process\ConfigDump\Import;
use Magento\MagentoCloud\Shell\ShellInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class ImportTest extends TestCase
{
    /**
     * @var Import
     */
    private $process;

    /**
     * @var ShellInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shellMock;

    /**
     * @var MagentoVersion|PHPUnit_Framework_MockObject_MockObject
     */
    private $magentoVersionMock;

    /**
     * @var LoggerInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->shellMock = $this->getMockForAbstractClass(ShellInterface::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->process = new Import(
            $this->shellMock,
            $this->magentoVersionMock,
            $this->loggerMock
        );
    }

    public function testExecute()
    {
        $this->shellMock->method('execute')
            ->with('php ./bin/magento app:config:import -n');
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->willReturn(true);

        $this->process->execute();
    }

    public function testExecuteMagento21()
    {
        $this->shellMock->expects($this->never())->method('execute');
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->willReturn(false);

        $this->process->execute();
    }
}
