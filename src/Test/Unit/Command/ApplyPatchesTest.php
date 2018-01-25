<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Command;

use Magento\MagentoCloud\Command\ApplyPatches;
use Magento\MagentoCloud\Patch\Manager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class ApplyPatchesTest.
 */
class ApplyPatchesTest extends TestCase
{
    /**
     * @var ApplyPatches
     */
    private $command;

    /**
     * @var Manager|Mock
     */
    private $managerMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->managerMock = $this->createMock(Manager::class);

        $this->command = new ApplyPatches(
            $this->loggerMock,
            $this->managerMock
        );
    }

    public function testExecute()
    {
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Patching started.'],
                ['Patching finished.']
            );
        $this->managerMock->expects($this->once())
            ->method('applyAll');

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
            ->method('info')
            ->with('Patching started.');
        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with('Some error');
        $this->managerMock->expects($this->once())
            ->method('applyAll')
            ->willThrowException(new \Exception('Some error'));

        $tester = new CommandTester(
            $this->command
        );
        $tester->execute([]);
    }
}
