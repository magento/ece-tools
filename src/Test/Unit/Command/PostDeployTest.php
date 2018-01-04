<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Command;

use Magento\MagentoCloud\App\Command\Wrapper;
use Magento\MagentoCloud\Command\PostDeploy;
use Magento\MagentoCloud\Process\ProcessInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @inheritdoc
 */
class PostDeployTest extends TestCase
{
    /**
     * @var PostDeploy
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
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->processMock = $this->getMockForAbstractClass(ProcessInterface::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);

        $this->command = new PostDeploy(
            $this->processMock,
            $this->loggerMock,
            new Wrapper($this->loggerMock)
        );
    }

    public function testExecute()
    {
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Starting post-deploy.'],
                ['Post-deploy is complete.']
            );
        $this->processMock->expects($this->once())
            ->method('execute');

        $tester = new CommandTester(
            $this->command
        );
        $tester->execute([]);

        $this->assertSame(Wrapper::CODE_SUCCESS, $tester->getStatusCode());
    }

    public function testExecuteWithException()
    {
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Starting post-deploy.');
        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with('Some error');
        $this->processMock->expects($this->once())
            ->method('execute')
            ->willThrowException(new \Exception('Some error'));

        $tester = new CommandTester(
            $this->command
        );
        $tester->execute([]);

        $this->assertSame(Wrapper::CODE_FAILURE, $tester->getStatusCode());
        $this->assertContains('Some error', $tester->getDisplay());
    }
}
