<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Util\BackgroundDirectoryCleaner;
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
     * @var DirectoryList|Mock
     */
    private $directoryListMock;

    /**
     * @var BackgroundDirectoryCleaner|Mock
     */
    private $cleanerMock;

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
        $this->cleanerMock = $this->getMockBuilder(BackgroundDirectoryCleaner::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->directoryListMock = $this->getMockBuilder(DirectoryList::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->processMock = $this->getMockBuilder(ProcessInterface::class)
            ->getMockForAbstractClass();

        $this->process = new DeployStaticContent(
            $this->processMock,
            $this->environmentMock,
            $this->loggerMock,
            $this->directoryListMock,
            $this->cleanerMock
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
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->cleanerMock->expects($this->once())
            ->method('backgroundClearDirectory')
            ->with('magento_root/pub/static');
        $this->cleanerMock->expects($this->once())
            ->method('backgroundDeleteDirectory')
            ->with('magento_root/var/view_preprocessed');
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
        $this->directoryListMock->expects($this->never())
            ->method('getMagentoRoot');
        $this->cleanerMock->expects($this->never())
            ->method('backgroundClearDirectory');
        $this->cleanerMock->expects($this->never())
            ->method('backgroundDeleteDirectory');
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

        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Application mode is ' . Environment::MAGENTO_PRODUCTION_MODE],
                ['Skipping static content deployment during deployment.']
            );

        $this->cleanerMock->expects($this->never())
            ->method('backgroundClearDirectory');

        $this->cleanerMock->expects($this->never())
            ->method('backgroundDeleteDirectory');

        $this->process->execute();
    }
}
