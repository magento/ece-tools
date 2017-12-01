<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\Urls;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Process\ProcessInterface;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use PHPUnit_Framework_MockObject_Matcher_InvokedCount as InvokedCount;

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
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentMock = $this->getMockBuilder(Environment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->processMock = $this->getMockBuilder(ProcessInterface::class)
            ->getMockForAbstractClass();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();

        $this->process = new Urls(
            $this->environmentMock,
            $this->processMock,
            $this->loggerMock
        );
    }

    /**
     * @inheritdoc
     */
    public function testExecute() {
        $this->environmentMock->expects($this->once())
            ->method('isMasterBranch')
            ->willReturn(false);
        $this->environmentMock->expects($this->once())
            ->method('isUpdateUrlsEnabled')
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
        $this->environmentMock->expects($envIsUpdateUrlsEnabledExpects)
            ->method('isUpdateUrlsEnabled')
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
            ]
        ];
    }
}
