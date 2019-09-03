<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Command;

use Magento\MagentoCloud\Command\Deploy;
use Magento\MagentoCloud\Scenario\Processor;
use Magento\MagentoCloud\Scenario\Exception\ProcessorException;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
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
     * @var FlagManager|MockObject
     */
    private $flagManagerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->processorMock = $this->createMock(Processor::class);
        $this->flagManagerMock = $this->createMock(FlagManager::class);

        $this->command = new Deploy(
            $this->processorMock,
            $this->flagManagerMock
        );
    }

    public function testExecute()
    {
        $this->processorMock->expects($this->once())
            ->method('execute')
            ->with([
                'scenario/deploy.xml'
            ]);
        $this->flagManagerMock->expects($this->never())
            ->method('set');

        $tester = new CommandTester(
            $this->command
        );
        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
    }

    /**
     * @expectedException \Magento\MagentoCloud\Scenario\Exception\ProcessorException
     * @expectedExceptionMessage Some error
     */
    public function testExecuteWithException()
    {
        $this->processorMock->expects($this->once())
            ->method('execute')
            ->with([
                'scenario/deploy.xml'
            ])
            ->willThrowException(new ProcessorException('Some error'));
        $this->flagManagerMock->expects($this->once())
            ->method('set')
            ->with(FlagManager::FLAG_DEPLOY_HOOK_IS_FAILED);

        $tester = new CommandTester(
            $this->command
        );
        $tester->execute([]);

        $this->assertSame(1, $tester->getStatusCode());
    }
}
