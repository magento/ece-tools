<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Command;

use Magento\MagentoCloud\App\Command\Wrapper;
use Magento\MagentoCloud\Command\Build;
use Magento\MagentoCloud\Process\ProcessInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Tester\CommandTester;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class BuildTest extends TestCase
{
    /**
     * @var Build
     */
    private $command;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var ProcessInterface|Mock
     */
    private $processMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->processMock = $this->getMockForAbstractClass(ProcessInterface::class);

        $this->command = new Build(
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
                ['Starting build.'],
                ['Building completed.']
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
