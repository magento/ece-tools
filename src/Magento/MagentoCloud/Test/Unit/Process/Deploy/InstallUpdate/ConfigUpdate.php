<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate;

use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate;
use Magento\MagentoCloud\Process\ProcessInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

class ConfigUpdateTest extends TestCase
{
    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var ProcessInterface|Mock
     */
    private $processMock;

    /**
     * @var ConfigUpdate
     */
    private $process;

    protected function setUp()
    {
        $this->processMock = $this->getMockBuilder(ProcessInterface::class)
            ->getMockForAbstractClass();

        $this->process = new ConfigUpdate(
            $this->loggerMock,
            $this->processMock
        );
    }

    public function testExecute()
    {
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating configuration from environment variables.');
        $this->processMock->expects($this->once())
            ->method('execute');

        $this->process->execute();
    }
}
