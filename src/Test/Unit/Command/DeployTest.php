<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Command;

use Magento\MagentoCloud\App\Command\Wrapper;
use Magento\MagentoCloud\Command\Deploy;
use Magento\MagentoCloud\Process\ProcessInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Tester\CommandTester;
use PHPUnit_Framework_MockObject_MockObject as Mock;

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
     * @var ProcessInterface|Mock
     */
    private $processMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var Wrapper|Mock
     */
    private $wrapperMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->processMock = $this->getMockForAbstractClass(ProcessInterface::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->wrapperMock = $this->createTestProxy(Wrapper::class, [$this->loggerMock]);

        $this->command = new Deploy(
            $this->processMock,
            $this->loggerMock,
            $this->wrapperMock
        );
    }

    public function testExecute()
    {
        $this->processMock->expects($this->once())
            ->method('execute');
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Starting deploy.'],
                ['Deployment completed.']
            );
        $this->loggerMock->expects($this->once())
            ->method('debug');

        $tester = new CommandTester(
            $this->command
        );
        $tester->execute([]);

        $this->assertSame(Wrapper::CODE_SUCCESS, $tester->getStatusCode());
    }
}
