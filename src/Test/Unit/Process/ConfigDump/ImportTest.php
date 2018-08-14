<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\ConfigDump;

use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Process\ConfigDump\Import;
use Magento\MagentoCloud\Shell\ExecBinMagento;
use PHPUnit\Framework\MockObject\MockObject;
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
     * @var ExecBinMagento|MockObject
     */
    private $shellMock;

    /**
     * @var MagentoVersion|MockObject
     */
    private $magentoVersionMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->shellMock = $this->createMock(ExecBinMagento::class);
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
            ->with('app:config:import');
        $this->magentoVersionMock->expects($this->once())
        ->method('isGreaterOrEqual')
        ->willReturn(true);

        $this->process->execute();
    }
}
