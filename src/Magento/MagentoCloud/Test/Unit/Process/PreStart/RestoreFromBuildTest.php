<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Build;

use Magento\MagentoCloud\Process\PreStart\RestoreFromBuild;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Util\BackgroundDirectoryCleaner;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class RestoreFromBuildTest extends TestCase
{
    /**
     * @var RestoreFromBuild
     */
    private $process;

    /**
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var ShellInterface|Mock
     */
    private $shellMock;

    /**
     * @var File|Mock
     */
    private $fileMock;

    /**
     * @var DirectoryList|Mock
     */
    private $directoryListMock;

    /**
     * @var BackgroundDirectoryCleaner|Mock
     */
    private $cleanerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentMock = $this->getMockBuilder(Environment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->shellMock = $this->getMockBuilder(ShellInterface::class)
            ->getMockForAbstractClass();
        $this->fileMock = $this->getMockBuilder(File::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->directoryListMock = $this->getMockBuilder(DirectoryList::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cleanerMock = $this->getMockBuilder(BackgroundDirectoryCleaner::class)
            ->disableOriginalConstructor()
            ->getMock();


        $this->process = new RestoreFromBuild(
            $this->environmentMock,
            $this->loggerMock,
            $this->shellMock,
            $this->fileMock,
            $this->directoryListMock,
            $this->cleanerMock
        );

        parent::setUp();
    }

    public function testExecuteAlreadyCompleted()
    {
        $this->environmentMock->expects($this->once())
            ->method('hasFlag')
            ->with(Environment::DEPLOY_READY_FLAG)
            ->willReturn(true);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Environment is ready for deployment. Aborting pre-start.');

        $this->process->execute();
    }
}
