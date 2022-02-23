<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Step\Deploy\InstallUpdate\ConfigUpdate\Urls;
use Magento\MagentoCloud\Step\StepException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class UrlsTest extends TestCase
{
    /**
     * @var Urls
     */
    private $step;

    /**
     * @var Environment|MockObject
     */
    private $environmentMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var DeployInterface|MockObject
     */
    private $stageConfigMock;

    /**
     * @var Urls\Environment|MockObject
     */
    private $environmentUrlMock;

    /**
     * @var Urls\Database|MockObject
     */
    private $databaseUrlMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);
        $this->databaseUrlMock = $this->createMock(Urls\Database::class);
        $this->environmentUrlMock = $this->createMock(Urls\Environment::class);

        $this->step = new Urls(
            $this->environmentMock,
            $this->loggerMock,
            $this->stageConfigMock,
            $this->databaseUrlMock,
            $this->environmentUrlMock
        );
    }

    public function testExecute()
    {
        $this->environmentMock->expects($this->once())
            ->method('isMasterBranch')
            ->willReturn(false);
        $this->stageConfigMock->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [DeployInterface::VAR_FORCE_UPDATE_URLS],
                [DeployInterface::VAR_UPDATE_URLS]
            )
            ->willReturnOnConsecutiveCalls(false, true);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating secure and unsecure URLs');
        $this->databaseUrlMock->expects($this->once())
            ->method('execute');
        $this->environmentUrlMock->expects($this->once())
            ->method('execute');

        $this->step->execute();
    }

    public function testExecuteForceUpdate()
    {
        $this->environmentMock->expects($this->never())
            ->method('isMasterBranch');
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_FORCE_UPDATE_URLS)
            ->willReturn(true);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating secure and unsecure URLs');
        $this->databaseUrlMock->expects($this->once())
            ->method('execute');
        $this->environmentUrlMock->expects($this->once())
            ->method('execute');

        $this->step->execute();
    }

    public function testExecuteSkippedIsMasterBranch()
    {
        $this->environmentMock->expects($this->once())
            ->method('isMasterBranch')
            ->willReturn(true);
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_FORCE_UPDATE_URLS)
            ->willReturn(false);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Skipping URL updates because we are deploying to a Production or Staging'));
        $this->databaseUrlMock->expects($this->never())
            ->method('execute');
        $this->environmentUrlMock->expects($this->never())
            ->method('execute');

        $this->step->execute();
    }

    public function testExecuteSkippedUpdateUrlsIsFalse()
    {
        $this->environmentMock->expects($this->once())
            ->method('isMasterBranch')
            ->willReturn(false);
        $this->stageConfigMock->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [DeployInterface::VAR_FORCE_UPDATE_URLS],
                [DeployInterface::VAR_UPDATE_URLS]
            )
            ->willReturnOnConsecutiveCalls(false, false);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Skipping URL updates because the URL_UPDATES variable is set to false.'));
        $this->databaseUrlMock->expects($this->never())
            ->method('execute');
        $this->environmentUrlMock->expects($this->never())
            ->method('execute');

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithException()
    {
        $exceptionMsg = 'Error';
        $exceptionCode = 111;

        $this->expectException(StepException::class);
        $this->expectExceptionMessage($exceptionMsg);
        $this->expectExceptionCode($exceptionCode);

        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_FORCE_UPDATE_URLS)
            ->willReturn(true);
        $this->databaseUrlMock->expects($this->once())
            ->method('execute')
            ->willThrowException(new GenericException($exceptionMsg, $exceptionCode));

        $this->step->execute();
    }
}
