<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\PreDeploy;

use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Filesystem\RecoverableDirectoryList;
use Magento\MagentoCloud\Process\Deploy\PreDeploy\RestoreWritableDirectories;
use Magento\MagentoCloud\Util\BuildDirCopier;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class RestoreWritableDirectoriesTest extends TestCase
{
    /**
     * @var RestoreWritableDirectories
     */
    private $process;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var BuildDirCopier|Mock
     */
    private $buildDirCopierMock;

    /**
     * @var RecoverableDirectoryList|Mock
     */
    private $recoverableDirectoryListMock;

    /**
     * @var FlagManager|Mock
     */
    private $flagManagerMock;

    protected function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->buildDirCopierMock = $this->createMock(BuildDirCopier::class);
        $this->recoverableDirectoryListMock = $this->getMockBuilder(RecoverableDirectoryList::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->flagManagerMock = $this->createMock(FlagManager::class);

        $this->process = new RestoreWritableDirectories(
            $this->loggerMock,
            $this->buildDirCopierMock,
            $this->recoverableDirectoryListMock,
            $this->flagManagerMock
        );
    }

    public function testExecute()
    {
        $this->recoverableDirectoryListMock->expects($this->once())
            ->method('getList')
            ->willReturn([
                ['directory' => 'app/etc', 'strategy' => 'copy'],
                ['directory' => 'pub/media', 'strategy' => 'copy'],
            ]);
        $this->buildDirCopierMock->expects($this->exactly(2))
            ->method('copy')
            ->withConsecutive(
                ['app/etc', 'copy'],
                ['pub/media', 'copy']
            );
        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with('Recoverable directories were copied back.');
        $this->flagManagerMock->expects($this->once())
            ->method('delete')
            ->with(FlagManager::FLAG_REGENERATE);

        $this->process->execute();
    }
}
