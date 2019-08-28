<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Command;

use Magento\MagentoCloud\Command\Deploy;
use Magento\MagentoCloud\Scenario\Processor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @inheritdoc
 */
class DeployTest extends TestCase
{
    /**
     * @var Deploy
     */
    private $command;

    /**
     * @var Processor|MockObject
     */
    private $processorMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->processorMock = $this->createMock(Processor::class);

        $this->command = new Deploy(
            $this->processorMock
        );
    }

    public function testExecute()
    {
        $this->processorMock->expects($this->once())
            ->method('execute')
            ->with([
                'scenario/deploy.xml'
            ]);

        $tester = new CommandTester(
            $this->command
        );
        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
    }
}
