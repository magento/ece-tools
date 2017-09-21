<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Build;

use Magento\MagentoCloud\Process\Build\ApplyPatches;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Util\PackageManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
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
            ->with('php vendor/bin/m2-apply-patches');
        $this->packageManagerMock->method('hasMagentoVersion')
            ->with('2.2')
            ->willReturn(true);

        $this->process->execute();
    }

    public function testExecuteWithoutPatches()
    {
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Applying patches.');
        $this->packageManagerMock->method('hasMagentoVersion')
            ->with('2.2')
            ->willReturn(true);
        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with('Patching was failed. Skipping.');
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->willThrowException(new \Exception('Patching failed.'));

        $this->process->execute();
    }
}
