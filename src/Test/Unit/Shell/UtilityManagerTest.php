<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Shell;

use Magento\MagentoCloud\Shell\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Shell\UtilityException;
use Magento\MagentoCloud\Shell\UtilityManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

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
     * @var ShellInterface|MockObject
     */
    private $shellMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->shellMock = $this->getMockForAbstractClass(ShellInterface::class);

        $this->utilityManager = new UtilityManager(
            $this->shellMock
        );
    }

    public function testGet(): void
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
                ['which ' . UtilityManager::UTILITY_SHELL, [], $processMock1],
                ['which ' . UtilityManager::UTILITY_TIMEOUT, [], $processMock2],
            ]);

        $this->assertSame(
            '/usr/bash',
            $this->utilityManager->get(UtilityManager::UTILITY_SHELL)
        );
    }

    public function testGetWithException(): void
    {
        $this->expectException(UtilityException::class);
        $this->expectExceptionMessage('Utility some_util not found');

        $processMock1 = $this->getMockForAbstractClass(ProcessInterface::class);
        $processMock1->expects($this->once())
            ->method('getOutput')
            ->willReturn('/usr/bash');
        $processMock2 = $this->getMockForAbstractClass(ProcessInterface::class);
        $processMock2->expects($this->once())
            ->method('getOutput')
            ->willReturn('/usr/timeout');
        $this->shellMock->method('execute')
            ->willReturnMap([
                ['which ' . UtilityManager::UTILITY_SHELL, [], $processMock1],
                ['which ' . UtilityManager::UTILITY_TIMEOUT, [], $processMock2],
            ]);

        $this->assertSame(
            '/usr/bash',
            $this->utilityManager->get('some_util')
        );
    }

    /**
     * @throws UtilityException
     */
    public function testGetRequiredWithException(): void
    {
        $this->expectException(UtilityException::class);
        $this->expectExceptionMessage('Required utility timeout was not found');

        $this->shellMock->method('execute')
            ->willThrowException(new ShellException('Shell error'));

        $this->assertSame(
            '/usr/bash',
            $this->utilityManager->get('some_util')
        );
    }
}
