<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\PreDeploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Process\Deploy\PreDeploy\RestoreWritableDirectories;
use Magento\MagentoCloud\Util\BuildDirCopier;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

class RestoreWritableDirectoriesTest extends TestCase
{
    /**
     * @var RestoreWritableDirectories
     */
    private $process;

    /**
     * @var File|Mock
     */
    private $fileMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var BuildDirCopier|Mock
     */
    private $buildDirCopierMock;

    /**
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @var DirectoryList|Mock
     */
    private $directoryListMock;

    protected function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->fileMock = $this->createMock(File::class);
        $this->buildDirCopierMock = $this->createMock(BuildDirCopier::class);
        $this->environmentMock = $this->getMockBuilder(Environment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->directoryListMock = $this->getMockBuilder(DirectoryList::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->process = new RestoreWritableDirectories(
            $this->loggerMock,
            $this->fileMock,
            $this->buildDirCopierMock,
            $this->environmentMock,
            $this->directoryListMock
        );
    }

    public function testExecute()
    {
        $this->directoryListMock->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with('magento_root/' . Environment::REGENERATE_FLAG)
            ->willReturn(true);
        $this->fileMock->expects($this->once())
            ->method('deleteFile')
            ->with('magento_root/' . Environment::REGENERATE_FLAG);
        $this->environmentMock->expects($this->once())
            ->method('getRecoverableDirectories')
            ->willReturn(['app/etc', 'pub/media']);
        $this->buildDirCopierMock->expects($this->exactly(2))
            ->method('copy')
            ->withConsecutive(
                ['app/etc'],
                ['pub/media']
            );
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Recoverable directories were copied back.'],
                ['Removing var/.regenerate flag']
            );

        $this->process->execute();
    }

    public function testExecuteFlagNotExists()
    {
        $this->directoryListMock->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with('magento_root/' . Environment::REGENERATE_FLAG)
            ->willReturn(false);
        $this->fileMock->expects($this->never())
            ->method('deleteFile');
        $this->environmentMock->expects($this->once())
            ->method('getRecoverableDirectories')
            ->willReturn(['app/etc', 'pub/media']);
        $this->buildDirCopierMock->expects($this->exactly(2))
            ->method('copy')
            ->withConsecutive(
                ['app/etc'],
                ['pub/media']
            );
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Recoverable directories were copied back.');

        $this->process->execute();
    }
}
