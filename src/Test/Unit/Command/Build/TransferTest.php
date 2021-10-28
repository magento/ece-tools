<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Command\Build;

use Magento\MagentoCloud\Command\Build\Transfer;
use Magento\MagentoCloud\Scenario\Processor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @inheritdoc
 */
class TransferTest extends TestCase
{
    /**
     * @var Transfer
     */
    private $command;

    /**
     * @var Processor|MockObject
     */
    private $processorMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->processorMock = $this->createMock(Processor::class);

        $this->command = new Transfer(
            $this->processorMock
        );
    }

    public function testExecute()
    {
        $this->processorMock->expects($this->once())
            ->method('execute')
            ->with(['scenario/build/transfer.xml']);

        $tester = new CommandTester(
            $this->command
        );
        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
    }
}
