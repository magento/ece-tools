<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Process\Build;

use Magento\MagentoCloud\Process\Build\BackupData;
use Magento\MagentoCloud\Process\ProcessException;
use Magento\MagentoCloud\Process\ProcessInterface;
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
    private $process;

    /**
     * @var ProcessInterface|MockObject
     */
    private $processMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->processMock = $this->getMockForAbstractClass(ProcessInterface::class);

        $this->process = new BackupData(
            $this->loggerMock,
            [$this->processMock]
        );
    }

    /**
     * @throws ProcessException
     */
    public function testExecute()
    {
        $this->loggerMock->expects($this->exactly(2))
            ->method('notice')
            ->withConsecutive(
                ['Copying data to the ./init directory'],
                ['End of copying data to the ./init directory']
            );
        $this->processMock->expects($this->once())
            ->method('execute');

        $this->process->execute();
    }
}
