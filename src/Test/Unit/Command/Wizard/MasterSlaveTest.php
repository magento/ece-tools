<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Command\Wizard;

use Magento\MagentoCloud\Command\Wizard\MasterSlave;
use Magento\MagentoCloud\Command\Wizard\Util\OutputFormatter;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritdoc
 */
class MasterSlaveTest extends TestCase
{
    /**
     * @var MasterSlave
     */
    private $command;

    /**
     * @var OutputFormatter|MockObject
     */
    private $outputFormatterMock;

    /**
     * @var DeployInterface|MockObject
     */
    private $deployConfigMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->outputFormatterMock = $this->createMock(OutputFormatter::class);
        $this->deployConfigMock = $this->getMockForAbstractClass(DeployInterface::class);

        $this->command = new MasterSlave(
            $this->outputFormatterMock,
            $this->deployConfigMock
        );
    }

    public function testExecute()
    {
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $this->deployConfigMock->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [DeployInterface::VAR_REDIS_USE_SLAVE_CONNECTION, true],
                [DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION, true],
            ]);
        $this->outputFormatterMock->expects($this->never())
            ->method('writeItem');
        $this->outputFormatterMock->expects($this->once())
            ->method('writeResult')
            ->with($outputMock, true, 'Slave connections are configured');

        $this->command->execute($inputMock, $outputMock);
    }

    public function testExecuteWithErrors()
    {
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $this->deployConfigMock->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [DeployInterface::VAR_REDIS_USE_SLAVE_CONNECTION, false],
                [DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION, false],
            ]);
        $this->outputFormatterMock->expects($this->exactly(2))
            ->method('writeItem')
            ->withConsecutive(
                [$outputMock, 'MySQL slave connection is not configured'],
                [$outputMock, 'Redis slave connection is not configured']
            );
        $this->outputFormatterMock->expects($this->once())
            ->method('writeResult')
            ->with($outputMock, false, 'Slave connections are not configured');

        $this->command->execute($inputMock, $outputMock);
    }
}
