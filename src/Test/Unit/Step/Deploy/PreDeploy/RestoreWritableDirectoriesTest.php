<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy\PreDeploy;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Filesystem\RecoverableDirectoryList;
use Magento\MagentoCloud\Step\Deploy\PreDeploy\RestoreWritableDirectories;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Util\BuildDirCopier;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class RestoreWritableDirectoriesTest extends TestCase
{
    /**
     * @var RestoreWritableDirectories
     */
    private $step;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var BuildDirCopier|MockObject
     */
    private $buildDirCopierMock;

    /**
     * @var RecoverableDirectoryList|MockObject
     */
    private $recoverableDirectoryListMock;

    /**
     * @var FlagManager|MockObject
     */
    private $flagManagerMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->buildDirCopierMock = $this->createMock(BuildDirCopier::class);
        $this->recoverableDirectoryListMock = $this->getMockBuilder(RecoverableDirectoryList::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->flagManagerMock = $this->createMock(FlagManager::class);

        $this->step = new RestoreWritableDirectories(
            $this->loggerMock,
            $this->buildDirCopierMock,
            $this->recoverableDirectoryListMock,
            $this->flagManagerMock
        );
    }

    public function testExecute(): void
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

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithException()
    {
        $exceptionMsg = 'Error';
        $exceptionCode = 111;

        $this->expectException(StepException::class);
        $this->expectExceptionMessage($exceptionMsg);
        $this->expectExceptionCode($exceptionCode);

        $this->recoverableDirectoryListMock->expects($this->once())
            ->method('getList')
            ->willReturn([]);
        $this->flagManagerMock->expects($this->once())
            ->method('delete')
            ->willThrowException(new GenericException($exceptionMsg, $exceptionCode));

        $this->step->execute();
    }
}
