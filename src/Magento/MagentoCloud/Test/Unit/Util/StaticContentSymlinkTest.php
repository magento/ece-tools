<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Util;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Util\StaticContentSymlink;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

class StaticContentSymlinkTest extends TestCase
{
    /**
     * @var ShellInterface|Mock
     */
    private $shellMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var File|Mock
     */
    private $fileMock;

    /**
     * @var DirectoryList|Mock
     */
    private $directoryListMock;

    /**
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @var StaticContentSymlink
     */
    private $staticContentSymlink;

    protected function setUp()
    {
        $this->shellMock = $this->getMockBuilder(ShellInterface::class)
            ->getMockForAbstractClass();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->fileMock = $this->createMock(File::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->environmentMock = $this->createMock(Environment::class);

        $this->staticContentSymlink = new StaticContentSymlink(
            $this->loggerMock,
            $this->shellMock,
            $this->environmentMock,
            $this->fileMock,
            $this->directoryListMock
        );
    }

    public function testCreate()
    {
        $root = '/path/to/root';
        $staticContentLocation = '/path/to/root/pub/static/';
        $buildDir = '/path/to/root/init/';

        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($root);
        $this->fileMock->expects($this->exactly(2))
            ->method('getRealPath')
            ->withConsecutive(
                [$root . '/pub/static'],
                [$root . '/init']
            )
            ->willReturnOnConsecutiveCalls(
                $staticContentLocation,
                $buildDir
            );

        $this->staticContentSymlink->create();
    }
}
