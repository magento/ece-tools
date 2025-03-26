<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy;

use Magento\MagentoCloud\Cron\JobUnlocker;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use Magento\MagentoCloud\Step\Deploy\UnlockCronJobs;
use Magento\MagentoCloud\Step\StepException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class UnlockCronJobsTest extends TestCase
{
    /**
     * @var UnlockCronJobs
     */
    private $step;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var JobUnlocker|MockObject
     */
    private $jobUnlockerMock;

    /**
     * @var MagentoVersion|MockObject
     */
    private $magentoVersionMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->jobUnlockerMock = $this->createMock(JobUnlocker::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);

        $this->step = new UnlockCronJobs(
            $this->jobUnlockerMock,
            $this->loggerMock,
            $this->magentoVersionMock
        );
    }

    /**
     * @throws StepException
     */
    public function testExecute(): void
    {
        $this->magentoVersionMock->method('isGreaterOrEqual')
            ->willReturn(false);
        $this->jobUnlockerMock->expects($this->once())
            ->method('unlockAll')
            ->willReturn(5);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('5 cron jobs were updated from status "running" to status "error"');

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithError(): void
    {
        $this->expectExceptionMessage('Some error');
        $this->expectException(StepException::class);

        $this->magentoVersionMock->method('isGreaterOrEqual')
            ->willThrowException(new UndefinedPackageException('Some error'));

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteNoJobsUpdated(): void
    {
        $this->magentoVersionMock->method('isGreaterOrEqual')
            ->willReturn(false);
        $this->jobUnlockerMock->expects($this->once())
            ->method('unlockAll')
            ->willReturn(0);
        $this->loggerMock->expects($this->never())
            ->method('info');

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testSkipExecute(): void
    {
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.2.2')
            ->willReturn(true);
        $this->jobUnlockerMock->expects($this->never())
            ->method('unlockAll');

        $this->step->execute();
    }
}
