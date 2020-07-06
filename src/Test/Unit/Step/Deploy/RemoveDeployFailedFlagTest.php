<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy;

use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Filesystem\Flag\Manager;
use Magento\MagentoCloud\Step\Deploy\RemoveDeployFailedFlag;
use Magento\MagentoCloud\Step\StepException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class RemoveDeployFailedFlagTest extends TestCase
{
    /**
     * @var RemoveDeployFailedFlag
     */
    private $step;

    /**
     * @var Manager|MockObject
     */
    private $flagManagerMock;

    /**
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @var FileList|MockObject
     */
    private $fileListMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->flagManagerMock = $this->createMock(Manager::class);
        $this->fileMock = $this->createMock(File::class);
        $this->fileListMock = $this->createMock(FileList::class);

        $this->step = new RemoveDeployFailedFlag(
            $this->flagManagerMock,
            $this->fileMock,
            $this->fileListMock
        );
    }

    /**
     * @throws StepException
     */
    public function testExecute(): void
    {
        $filePath = 'file/path/name.txt';
        $this->flagManagerMock->expects($this->exactly(3))
            ->method('delete')
            ->withConsecutive(
                [Manager::FLAG_DEPLOY_HOOK_IS_FAILED],
                [Manager::FLAG_IGNORE_SPLIT_DB],
                [Manager::FLAG_ENV_FILE_ABSENCE]
            );
        $this->fileListMock->expects($this->once())
            ->method('getCloudErrorLog')
            ->willReturn($filePath);
        $this->fileMock->expects($this->once())
            ->method('deleteFile')
            ->with($filePath);

        $this->step->execute();
    }

    public function testExceptionType()
    {
        $this->expectException(StepException::class);
        $this->flagManagerMock->expects($this->once())
            ->method('delete')
            ->willThrowException(new \Exception('txt'));
        $this->step->execute();
    }
}
