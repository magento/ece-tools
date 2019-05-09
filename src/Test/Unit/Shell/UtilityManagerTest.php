<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Shell;

use Magento\MagentoCloud\Shell\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Shell\UtilityManager;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class UtilityManagerTest extends TestCase
{
    /**
     * @var UtilityManager
     */
    private $utilityManager;

    /**
     * @var ShellInterface|Mock
     */
    private $shellMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->shellMock = $this->getMockForAbstractClass(ShellInterface::class);

        $this->utilityManager = new UtilityManager(
            $this->shellMock
        );
    }

    public function testGet()
    {
        $processMock1 = $this->getMockForAbstractClass(ProcessInterface::class);
        $processMock1->expects($this->once())
            ->method('getOutput')
            ->willReturn("/usr/bash\n/usr/bin/bash");
        $processMock2 = $this->getMockForAbstractClass(ProcessInterface::class);
        $processMock2->expects($this->once())
            ->method('getOutput')
            ->willReturn('/usr/timeout');
        $this->shellMock->expects($this->any())
            ->method('execute')
            ->willReturnMap([
                ['which ' . UtilityManager::UTILITY_BASH, [], $processMock1],
                ['which ' . UtilityManager::UTILITY_TIMEOUT, [], $processMock2],
            ]);

        $this->assertSame(
            '/usr/bash',
            $this->utilityManager->get(UtilityManager::UTILITY_BASH)
        );
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Utility some_util not found
     */
    public function testGetWithException()
    {
        $processMock1 = $this->getMockForAbstractClass(ProcessInterface::class);
        $processMock1->expects($this->once())
            ->method('getOutput')
            ->willReturn('/usr/bash');
        $processMock2 = $this->getMockForAbstractClass(ProcessInterface::class);
        $processMock2->expects($this->once())
            ->method('getOutput')
            ->willReturn('/usr/timeout');
        $this->shellMock->expects($this->any())
            ->method('execute')
            ->willReturnMap([
                ['which ' . UtilityManager::UTILITY_BASH, [], $processMock1],
                ['which ' . UtilityManager::UTILITY_TIMEOUT, [], $processMock2],
            ]);

        $this->assertSame(
            '/usr/bash',
            $this->utilityManager->get('some_util')
        );
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Required utility timeout was not found
     */
    public function testGetRequiredWithException()
    {
        $this->shellMock->expects($this->any())
            ->method('execute')
            ->willThrowException(new \Exception('Shell error'));

        $this->assertSame(
            '/usr/bash',
            $this->utilityManager->get('some_util')
        );
    }
}
