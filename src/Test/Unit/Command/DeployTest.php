<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Command;

use Magento\MagentoCloud\Command\Deploy;
use Magento\MagentoCloud\Filesystem\Flag\Manager;
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
     * @var Manager|MockObject
     */
    private $flagManagerMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->processorMock = $this->createMock(Processor::class);
        $this->flagManagerMock = $this->createMock(Manager::class);

        $this->command = new Deploy(
            $this->processorMock,
            $this->flagManagerMock
        );
    }

    public function testExecute(): void
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
