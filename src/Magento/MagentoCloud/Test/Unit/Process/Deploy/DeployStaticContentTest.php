<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Util\StaticContentCleaner;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Magento\MagentoCloud\Process\Deploy\DeployStaticContent;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class DeployStaticContentTest extends TestCase
{
    /**
     * @var DeployStaticContent
     */
    private $process;

    /**
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @var ShellInterface|Mock
     */
    private $shellMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var StaticContentCleaner|Mock
     */
    private $staticContentCleanerMock;

    /**
     * @var ProcessInterface|Mock
     */
    private $processMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->shellMock = $this->getMockBuilder(ShellInterface::class)
            ->getMockForAbstractClass();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->staticContentCleanerMock = $this->createMock(StaticContentCleaner::class);
        $this->processMock = $this->getMockBuilder(ProcessInterface::class)
            ->getMockForAbstractClass();

        $this->process = new DeployStaticContent(
            $this->processMock,
            $this->environmentMock,
            $this->loggerMock,
            $this->staticContentCleanerMock
        );
    }

    public function testExecute()
    {
        $this->environmentMock->expects($this->once())
            ->method('getApplicationMode')
            ->willReturn(Environment::MAGENTO_PRODUCTION_MODE);
        $this->environmentMock->expects($this->once())
            ->method('isDeployStaticContent')
            ->willReturn(true);
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Application mode is ' . Environment::MAGENTO_PRODUCTION_MODE],
                ['Generating fresh static content']
            );
        $this->environmentMock->expects($this->once())
            ->method('doCleanStaticFiles')
            ->willReturn(true);
        $this->staticContentCleanerMock->expects($this->once())
            ->method('clean');
        $this->processMock->expects($this->once())
            ->method('execute');

        $this->process->execute();
    }

    public function testExecuteWithoutCleaning()
    {
        $this->environmentMock->expects($this->once())
            ->method('getApplicationMode')
            ->willReturn(Environment::MAGENTO_PRODUCTION_MODE);
        $this->environmentMock->expects($this->once())
            ->method('isDeployStaticContent')
            ->willReturn(true);
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Application mode is ' . Environment::MAGENTO_PRODUCTION_MODE],
                ['Generating fresh static content']
            );
        $this->environmentMock->expects($this->once())
            ->method('doCleanStaticFiles')
            ->willReturn(false);
        $this->staticContentCleanerMock->expects($this->never())
            ->method('clean');
        $this->processMock->expects($this->once())
            ->method('execute');

        $this->process->execute();
    }

    public function testExecuteNonProductionMode()
    {
        $this->environmentMock->expects($this->once())
            ->method('getApplicationMode')
            ->willReturn('Developer');
        $this->environmentMock->expects($this->never())
            ->method('isDeployStaticContent');
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Application mode is Developer');

        $this->process->execute();
    }

    public function testExecuteDoNotDeploy()
    {
        $this->environmentMock->expects($this->once())
            ->method('getApplicationMode')
            ->willReturn(Environment::MAGENTO_PRODUCTION_MODE);
        $this->environmentMock->expects($this->once())
            ->method('isDeployStaticContent')
            ->willReturn(false);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->withConsecutive(
                ['Application mode is ' . Environment::MAGENTO_PRODUCTION_MODE]
            );
        $this->environmentMock->expects($this->never())
            ->method('doCleanStaticFiles');

        $this->process->execute();
    }
}
