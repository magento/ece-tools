<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Build;

use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Process\Build\MarshallFiles;
use Magento\MagentoCloud\Shell\ShellInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class MarshallFilesTest extends TestCase
{
    /**
     * @var MarshallFiles
     */
    private $process;

    /**
     * @var ShellInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shellMock;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerMock;

    /**
     * @var File|\PHPUnit_Framework_MockObject_MockObject
     */
    private $fileMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->shellMock = $this->getMockBuilder(ShellInterface::class)
            ->getMockForAbstractClass();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->fileMock = $this->getMockBuilder(File::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->process = new MarshallFiles(
            $this->shellMock,
            $this->loggerMock,
            $this->fileMock
        );
    }

    public function testExecute()
    {
        $this->shellMock->expects($this->exactly(3))
            ->method('execute')
            ->withConsecutive(
                ['rm -rf generated/code/*'],
                ['rm -rf generated/metadata/*'],
                ['rm -rf var/cache']
            );
        $this->fileMock->expects($this->exactly(2))
            ->method('copy')
            ->withConsecutive(
                [MAGENTO_ROOT . 'app/etc/di.xml', MAGENTO_ROOT . 'app/di.xml'],
                [MAGENTO_ROOT . 'app/etc/enterprise/di.xml', MAGENTO_ROOT . 'app/enterprise/di.xml']
            );
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with(MAGENTO_ROOT . 'app/enterprise')
            ->willReturn(true);

        $this->process->execute();
    }

    public function testExecuteNoEnterpriseFolder()
    {
        $this->shellMock->expects($this->exactly(3))
            ->method('execute')
            ->withConsecutive(
                ['rm -rf generated/code/*'],
                ['rm -rf generated/metadata/*'],
                ['rm -rf var/cache']
            );
        $this->fileMock->expects($this->exactly(2))
            ->method('copy')
            ->withConsecutive(
                [MAGENTO_ROOT . 'app/etc/di.xml', MAGENTO_ROOT . 'app/di.xml'],
                [MAGENTO_ROOT . 'app/etc/enterprise/di.xml', MAGENTO_ROOT . 'app/enterprise/di.xml']
            );
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with(MAGENTO_ROOT . 'app/enterprise')
            ->willReturn(false);
        $this->fileMock->expects($this->once())
            ->method('createDirectory')
            ->with(MAGENTO_ROOT . 'app/enterprise', 0777);

        $this->process->execute();
    }

    public function testExecuteWithException()
    {
        $this->shellMock->expects($this->exactly(3))
            ->method('execute')
            ->withConsecutive(
                ['rm -rf generated/code/*'],
                ['rm -rf generated/metadata/*'],
                ['rm -rf var/cache']
            );
        $this->fileMock->expects($this->any())
            ->method('copy')
            ->willThrowException(new FileSystemException('Some exception'));
        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with('Some exception');

        $this->process->execute();
    }
}
