<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Command;

use Magento\MagentoCloud\Command\CronKill;
use Magento\MagentoCloud\Step\StepInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritdoc
 */
class CronKillTest extends TestCase
{
    /**
     * @var CronKill
     */
    private $command;

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
    protected function setUp()
    {
        $this->stepMock = $this->getMockForAbstractClass(StepInterface::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);

        $this->command = new CronKill(
            $this->stepMock,
            $this->loggerMock
        );
    }

    /**
     * @throws \Exception
     */
    public function testExecute()
    {
        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->createMock(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->createMock(OutputInterface::class);

        $this->stepMock->expects($this->once())
            ->method('execute');

        $this->command->execute($inputMock, $outputMock);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Some error
     *
     * @throws \Exception
     */
    public function testExecuteWithException()
    {
        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->createMock(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->createMock(OutputInterface::class);

        $this->stepMock->expects($this->once())
            ->method('execute')
            ->willThrowException(new \Exception('Some error'));

        $this->command->execute($inputMock, $outputMock);
    }
}
