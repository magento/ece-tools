<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Build;

use Magento\MagentoCloud\Step\Build\BackupData;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class BackupDataTest extends TestCase
{
    /**
     * @var BackupData
     */
    private $step;

    /**
     * @var StepInterface|MockObject
     */
    private $stepMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->stepMock = $this->createMock(StepInterface::class);

        $this->step = new BackupData(
            $this->loggerMock,
            [$this->stepMock]
        );
    }

    /**
     * @throws StepException
     */
    public function testExecute()
    {
        $this->loggerMock->expects($this->exactly(2))
            ->method('notice')
            // withConsecutive() alternative.
            ->willReturnCallback(function (string $axis) {
                static $series = [
                    'Copying data to the ./init directory',
                    'End of copying data to the ./init directory'
                ];
                $this->assertSame(array_shift($series), $axis);
            });
        $this->stepMock->expects($this->once())
            ->method('execute');

        $this->step->execute();
    }
}
