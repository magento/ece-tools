<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\PreDeploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Process\Deploy\PreDeploy\RestoreWritableDirectories;
use Magento\MagentoCloud\Util\BuildDirCopier;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

class RestoreWritableDirectoriesTest extends TestCase
{
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
     * @var RestoreWritableDirectories
     */
    private $process;


    protected function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->fileMock = $this->createMock(File::class);
        $this->buildDirCopierMock = $this->createMock(BuildDirCopier::class);

        $this->process = new RestoreWritableDirectories(
            $this->loggerMock,
            $this->fileMock,
            $this->buildDirCopierMock
        );
    }

    public function testExecute()
    {
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with(Environment::REGENERATE_FLAG)
            ->willReturn(true);
        $this->fileMock->expects($this->once())
            ->method('deleteFile')
            ->with(Environment::REGENERATE_FLAG);
        $this->buildDirCopierMock->expects($this->exactly(2))
            ->method('copy')
            ->withConsecutive(
                ['app/etc'],
                ['pub/media']
            );
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Copying writable directories back.'],
                ['Removing var/.regenerate flag']
            );

        $this->process->execute();
    }

    public function testExecuteFlagNotExists()
    {
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with(Environment::REGENERATE_FLAG)
            ->willReturn(false);
        $this->fileMock->expects($this->never())
            ->method('deleteFile');
        $this->buildDirCopierMock->expects($this->exactly(2))
            ->method('copy')
            ->withConsecutive(
                ['app/etc'],
                ['pub/media']
            );
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Copying writable directories back.');

        $this->process->execute();
    }
}
