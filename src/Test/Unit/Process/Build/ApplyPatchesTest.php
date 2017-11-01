<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Build;

use Magento\MagentoCloud\Process\Build\ApplyPatches;
use Magento\MagentoCloud\Shell\ShellInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Package\Manager as PackageManager;
use PHPUnit_Framework_MockObject_MockObject as Mock;

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
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var ShellInterface|Mock
     */
    private $shellMock;

    /**
     * @var PackageManager|Mock
     */
    private $packageManagerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->shellMock = $this->getMockBuilder(ShellInterface::class)
            ->getMockForAbstractClass();
        $this->packageManagerMock = $this->getMockBuilder(PackageManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->process = new ApplyPatches(
            $this->shellMock,
            $this->loggerMock,
            $this->packageManagerMock
        );

        parent::setUp();
    }

    public function testExecute()
    {
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Applying patches.');
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('php ./vendor/bin/m2-apply-patches');
        $this->packageManagerMock->method('has')
            ->with('magento/ece-patches')
            ->willReturn(true);

        $this->process->execute();
    }

    public function testExecuteWithoutPatches()
    {
        $this->loggerMock->method('info')
            ->with('Applying patches.');
        $this->loggerMock->method('warning')
            ->with('Package with patches was not found.');
        $this->packageManagerMock->method('has')
            ->with('magento/ece-patches')
            ->willReturn(false);
        $this->shellMock->expects($this->never())
            ->method('execute');

        $this->process->execute();
    }
}
