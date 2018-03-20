<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Util;

use Magento\MagentoCloud\Util\StaticContentCleaner;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class StaticContentCleanerTest extends TestCase
{
    /**
     * @var DirectoryList|Mock
     */
    private $directoryListMock;

    /**
     * @var File|Mock
     */
    private $fileMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var StaticContentCleaner
     */
    private $staticContentCleaner;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->fileMock = $this->createMock(File::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);

        $this->staticContentCleaner = new StaticContentCleaner(
            $this->directoryListMock,
            $this->fileMock,
            $this->loggerMock
        );
    }

    public function testClean()
    {
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(['Clearing pub/static'], ['Clearing var/view_preprocessed']);
        $this->directoryListMock->expects($this->exactly(2))
            ->method('getPath')
            ->withConsecutive([DirectoryList::DIR_STATIC], [DirectoryList::DIR_VIEW_PREPROCESSED])
            ->willReturnOnConsecutiveCalls('pub/static', 'var/view_preprocessed');
        $this->fileMock->expects($this->exactly(2))
            ->method('backgroundClearDirectory')
            ->withConsecutive(['pub/static'], ['var/view_preprocessed']);

        $this->staticContentCleaner->clean();
    }
}
