<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate;

use Magento\MagentoCloud\Process\Deploy\InstallUpdate\Update;
use Magento\MagentoCloud\Process\ProcessInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class UpdateTest extends TestCase
{
    /**
     * @var ProcessInterface|Mock
     */
    private $processMock;

    /**
     * @var Update
     */
    private $process;

    protected function setUp()
    {
        $this->processMock = $this->getMockBuilder(ProcessInterface::class)
            ->getMockForAbstractClass();

        $this->process = new Update($this->processMock);
    }

    public function testExecute()
    {
        $this->processMock->expects($this->once())
            ->method('execute');

        $this->process->execute();
    }
}
