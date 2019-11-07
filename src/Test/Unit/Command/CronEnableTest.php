<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Command;

use Magento\MagentoCloud\Command\CronEnable;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Step\PostDeploy\EnableCron;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @inheritDoc
 */
class CronEnableTest extends TestCase
{
    /**
     * @var CronEnable
     */
    private $command;

    /**
     * @var EnableCron|MockObject
     */
    private $enableCronMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->enableCronMock = $this->createMock(EnableCron::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);

        $this->command = new CronEnable($this->enableCronMock, $this->loggerMock);
    }

    /**
     * @inheritDoc
     */
    public function testExecute()
    {
        $this->enableCronMock->expects($this->once())
            ->method('execute');
        $this->loggerMock->expects($this->never())
            ->method('critical');

        $tester = new CommandTester($this->command);
        $tester->execute([]);
        $this->assertSame(0, $tester->getStatusCode());
    }

    /**
     * @expectedExceptionMessage save error
     * @expectedException \Magento\MagentoCloud\Filesystem\FileSystemException
     */
    public function testExecuteWithException()
    {
        $this->enableCronMock->expects($this->once())
            ->method('execute')
            ->willThrowException(new FileSystemException('save error'));
        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with('save error');

        $tester = new CommandTester($this->command);
        $tester->execute([]);
        $this->assertSame(1, $tester->getStatusCode());
    }
}
