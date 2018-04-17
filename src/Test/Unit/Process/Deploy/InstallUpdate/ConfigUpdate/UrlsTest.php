<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\Urls;
use PHPUnit\Framework\MockObject\Matcher\InvokedCount;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Process\ProcessInterface;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class UrlsTest extends TestCase
{
    /**
     * @var Urls
     */
    private $process;

    /**
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @var ProcessInterface|Mock
     */
    private $processMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var DeployInterface|Mock
     */
    private $stageConfigMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->processMock = $this->getMockForAbstractClass(ProcessInterface::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);

        $this->process = new Urls(
            $this->environmentMock,
            $this->processMock,
            $this->loggerMock,
            $this->stageConfigMock
        );
    }

    /**
     * @inheritdoc
     */
    public function testExecute()
    {
        $this->environmentMock->expects($this->once())
            ->method('isMasterBranch')
            ->willReturn(false);
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_UPDATE_URLS)
            ->willReturn(true);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating secure and unsecure URLs');
        $this->processMock->expects($this->once())
            ->method('execute');

        $this->process->execute();
    }

    /**
     * @param bool $envIsMasterBranchWillReturn
     * @param InvokedCount $envIsUpdateUrlsEnabledExpects
     * @dataProvider executeSkippedDataProvider
     */
    public function testExecuteSkipped(
        bool $envIsMasterBranchWillReturn,
        InvokedCount $envIsUpdateUrlsEnabledExpects
    ) {
        $this->environmentMock->expects($this->once())
            ->method('isMasterBranch')
            ->willReturn($envIsMasterBranchWillReturn);
        $this->stageConfigMock->expects($envIsUpdateUrlsEnabledExpects)
            ->method('get')
            ->with(DeployInterface::VAR_UPDATE_URLS)
            ->willReturn(false);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Skipping URL updates');

        $this->process->execute();
    }

    /**
     * @return array
     */
    public function executeSkippedDataProvider(): array
    {
        return [
            [
                'envIsMasterBranchWillReturn' => true,
                'envIsUpdateUrlsEnabledExpects' => $this->never(),
            ],
            [
                'envIsMasterBranchWillReturn' => false,
                'envIsMasterBranchExpects' => $this->once(),
            ],
        ];
    }
}
