<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Build;

use Magento\MagentoCloud\Config\Module;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Process\Build\RefreshModules;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class RefreshModulesTest extends TestCase
{
    /**
     * @var ProcessInterface
     */
    private $process;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var Module|MockObject
     */
    private $configMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->configMock = $this->createMock(Module::class);

        $this->process = new RefreshModules(
            $this->loggerMock,
            $this->configMock
        );
    }

    public function testExecute()
    {
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Reconciling installed modules with shared config.');
        $this->configMock->expects($this->once())
            ->method('refresh');

        $this->process->execute();
    }
}
