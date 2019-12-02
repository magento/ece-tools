<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step;

use Magento\MagentoCloud\Config\Magento\Env\WriterInterface;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Step\SetProductionMode;
use Magento\MagentoCloud\Step\StepException;
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
    private $step;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var WriterInterface|Mock
     */
    private $writer;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->writer = $this->getMockForAbstractClass(WriterInterface::class);

        $this->step = new SetProductionMode(
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

        $this->step->execute();
    }

    public function testExecuteWitException()
    {
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('can\'t update file');

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->willReturn("Set Magento application mode to 'production'");
        $this->writer->expects($this->once())
            ->method('update')
            ->willThrowException(new FileSystemException('can\'t update file'));

        $this->step->execute();
    }
}
