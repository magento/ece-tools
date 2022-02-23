<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Build;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Config\Module;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Step\Build\RefreshModules;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Magento\MagentoCloud\Shell\ShellException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class RefreshModulesTest extends TestCase
{
    /**
     * @var StepInterface
     */
    private $step;

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
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->configMock = $this->createMock(Module::class);

        $this->step = new RefreshModules(
            $this->loggerMock,
            $this->configMock
        );
    }

    public function testExecute()
    {
        $this->loggerMock->expects($this->exactly(2))
            ->method('notice')
            ->withConsecutive(
                ['Reconciling installed modules with shared config.'],
                ['End of reconciling modules.']
            );
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('The following modules have been enabled:' . PHP_EOL . 'module1' . PHP_EOL . 'module2');
        $this->configMock->expects($this->once())
            ->method('refresh')
            ->willReturn(['module1', 'module2']);

        $this->step->execute();
    }

    public function testExecuteNoModulesChanged()
    {
        $this->loggerMock->expects($this->exactly(2))
            ->method('notice')
            ->withConsecutive(
                ['Reconciling installed modules with shared config.'],
                ['End of reconciling modules.']
            );
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('No modules were changed.');
        $this->configMock->expects($this->once())
            ->method('refresh')
            ->willReturn([]);

        $this->step->execute();
    }

    public function testExecuteWithException()
    {
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('some error');
        $this->expectExceptionCode(10);

        $this->configMock->expects($this->once())
            ->method('refresh')
            ->willThrowException(new GenericException('some error', 10));

        $this->step->execute();
    }

    public function testExecuteWithShellException()
    {
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('some error');
        $this->expectExceptionCode(Error::BUILD_MODULE_ENABLE_COMMAND_FAILED);

        $this->configMock->expects($this->once())
            ->method('refresh')
            ->willThrowException(new ShellException('some error'));

        $this->step->execute();
    }

    public function testExecuteWithFileSystemException()
    {
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('some error');
        $this->expectExceptionCode(Error::BUILD_CONFIG_PHP_IS_NOT_WRITABLE);

        $this->configMock->expects($this->once())
            ->method('refresh')
            ->willThrowException(new FileSystemException('some error'));

        $this->step->execute();
    }
}
