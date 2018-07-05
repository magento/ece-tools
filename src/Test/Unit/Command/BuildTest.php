<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Command;

use Magento\MagentoCloud\Command\Build;
use Magento\MagentoCloud\Package\Manager as PackageManager;
use Magento\MagentoCloud\Process\ProcessInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Tester\CommandTester;

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
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var ProcessInterface|MockObject
     */
    private $processMock;

    /**
     * @var PackageManager|MockObject
     */
    private $packageManagerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->processMock = $this->getMockBuilder(ProcessInterface::class)
            ->getMockForAbstractClass();
        $this->packageManagerMock = $this->createMock(PackageManager::class);

        $this->command = new Build(
            $this->processMock,
            $this->loggerMock,
            $this->packageManagerMock
        );
    }

    public function testExecute()
    {
        $this->loggerMock->expects($this->exactly(2))
            ->method('notice')
            ->withConsecutive(
                ['Starting build. Some info.'],
                ['Building completed.']
            );
        $this->processMock->expects($this->once())
            ->method('execute');
        $this->packageManagerMock->expects($this->once())
            ->method('getPrettyInfo')
            ->willReturn('Some info.');

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
            ->method('notice')
            ->with('Starting build. Some info.');
        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with('Some error');
        $this->processMock->expects($this->once())
            ->method('execute')
            ->willThrowException(new \Exception('Some error'));
        $this->packageManagerMock->expects($this->once())
            ->method('getPrettyInfo')
            ->willReturn('Some info.');

        $tester = new CommandTester(
            $this->command
        );
        $tester->execute([]);
    }
}
