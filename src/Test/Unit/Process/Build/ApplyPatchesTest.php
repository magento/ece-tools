<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Build;

use Magento\MagentoCloud\Patch\Manager;
use Magento\MagentoCloud\Process\Build\ApplyPatches;
use Magento\MagentoCloud\Process\ProcessException;
use Magento\MagentoCloud\Shell\ShellException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ApplyPatchesTest extends TestCase
{
    /**
     * @var ApplyPatches
     */
    private $process;

    /**
     * @var Manager|MockObject
     */
    private $managerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->managerMock = $this->createMock(Manager::class);

        $this->process = new ApplyPatches(
            $this->managerMock
        );

        parent::setUp();
    }

    /**
     * @throws ProcessException
     */
    public function testExecute()
    {
        $this->managerMock->expects($this->once())
            ->method('apply');

        $this->process->execute();
    }

    /**
     * @expectedException \Magento\MagentoCloud\Process\ProcessException
     * @expectedExceptionMessage Some error
     *
     * @throws ProcessException
     */
    public function testExecuteWithException()
    {
        $this->managerMock->expects($this->once())
            ->method('apply')
            ->willThrowException(new ShellException('Some error'));

        $this->process->execute();
    }
}
