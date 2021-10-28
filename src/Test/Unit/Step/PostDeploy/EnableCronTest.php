<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\PostDeploy;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Cron\Switcher;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Step\PostDeploy\EnableCron;
use Magento\MagentoCloud\Step\StepException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Test class for Magento\MagentoCloud\Process\Deploy\EnableCron
 */
class EnableCronTest extends TestCase
{
    /**
     * @var EnableCron
     */
    private $step;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var Switcher|MockObject
     */
    private $cronSwitcherMock;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->cronSwitcherMock = $this->createMock(Switcher::class);

        $this->step = new EnableCron(
            $this->loggerMock,
            $this->cronSwitcherMock
        );
    }

    /**
     * @throws StepException
     */
    public function testExecute()
    {
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Enable cron');
        $this->cronSwitcherMock->expects($this->once())
            ->method('enable');

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithFileSystemException()
    {
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('some error');
        $this->expectExceptionCode(Error::PD_ENV_PHP_IS_NOT_WRITABLE);

        $this->cronSwitcherMock->expects($this->once())
            ->method('enable')
            ->willThrowException(new FileSystemException('some error'));

        $this->step->execute();
    }
}
