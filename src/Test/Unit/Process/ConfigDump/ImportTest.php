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
use PHPUnit_Framework_MockObject_MockObject as Mock;

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
     * @var ShellInterface|Mock
     */
    private $shellMock;

    /**
     * @var MagentoVersion|Mock
     */
    private $magentoVersionMock;

    /**
     * @var LoggerInterface|Mock
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
        $this->magentoVersionMock->method('isGreaterOrEqual')
            ->willReturn(true);
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('php ./bin/magento app:config:import -n');

        $this->process->execute();
    }

    public function testIsAvailable()
    {
        $this->loggerMock->expects($this->never())
            ->method('info');

        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->willReturn(true);

        $this->assertSame(true, $this->process->isAvailable());
    }

    public function testIsNotAvailable()
    {
        $this->loggerMock->expects($this->once())
            ->method('info');

        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->willReturn(false);

        $this->assertSame(false, $this->process->isAvailable());
    }
}
