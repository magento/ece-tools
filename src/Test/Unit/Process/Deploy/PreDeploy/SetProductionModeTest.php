<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\PreDeploy;

use Magento\MagentoCloud\Config\Deploy\Writer;
use Magento\MagentoCloud\Process\Deploy\PreDeploy\SetProductionMode;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class SetProductionModeTest extends TestCase
{
    /**
     * @var SetProductionMode
     */
    private $process;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var Writer|Mock
     */
    private $writer;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->writer = $this->createMock(Writer::class);

        $this->process = new SetProductionMode(
            $this->loggerMock,
            $this->writer
        );
    }

    public function testExecute()
    {
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->willReturn("Set Magento application mode to 'production'");
        $this->writer->expects($this->once())
            ->method('update')
            ->with(['MAGE_MODE' => 'production']);

        $this->process->execute();
    }
}
