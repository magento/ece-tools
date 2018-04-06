<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Command;

use Magento\MagentoCloud\Command\Deploy;
use Magento\MagentoCloud\Process\ProcessInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
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
     * @var ProcessInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $processMock;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerMock;

    /**
     * @var FlagManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $flagManagerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->processMock = $this->getMockForAbstractClass(ProcessInterface::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->flagManagerMock = $this->createMock(FlagManager::class);

        $this->command = new Deploy(
            $this->processMock,
            $this->loggerMock,
            $this->flagManagerMock
        );
    }

    public function testExecute()
    {
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(['Starting deploy.'], ['Deployment completed.']);
        $this->processMock->expects($this->once())
            ->method('execute');
        $this->flagManagerMock->expects($this->never())
            ->method('set');

        $tester = new CommandTester(
            $this->command
        );
        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Some error
     */
    public function testExecuteWithException()
    {
        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with('Some error');
        $this->processMock->expects($this->once())
            ->method('execute')
            ->willThrowException(new \Exception('Some error'));
        $this->flagManagerMock->expects($this->once())
            ->method('set')
            ->with(FlagManager::FLAG_DEPLOY_HOOK_IS_FAILED);

        $tester = new CommandTester(
            $this->command
        );
        $tester->execute([]);
    }
}
