<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Command;

use Magento\MagentoCloud\Command\ApplyPatches;
use Magento\MagentoCloud\Patch\Manager;
use Magento\MagentoCloud\Shell\ShellException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
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
     * @var Manager|MockObject
     */
    private $managerMock;

    protected function setUp()
    {
        $this->managerMock = $this->createMock(Manager::class);

        $this->command = new ApplyPatches(
            $this->managerMock
        );
    }

    public function testExecute()
    {
        $this->managerMock->expects($this->once())
            ->method('apply');

        $tester = new CommandTester(
            $this->command
        );
        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
    }

    /**
     * @expectedException \Magento\MagentoCloud\Shell\ShellException
     * @expectedExceptionMessage Some error
     */
    public function testExecuteWithException()
    {
        $this->managerMock->expects($this->once())
            ->method('apply')
            ->willThrowException(new ShellException('Some error'));

        $tester = new CommandTester(
            $this->command
        );
        $tester->execute([]);
    }
}
