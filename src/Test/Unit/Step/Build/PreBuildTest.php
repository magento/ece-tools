<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Build;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Config\Stage\BuildInterface;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Step\Build\PreBuild;
use Magento\MagentoCloud\Step\StepException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class PreBuildTest extends TestCase
{
    /**
     * @var PreBuild
     */
    private $step;

    /**
     * @var BuildInterface|MockObject
     */
    private $stageConfigMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var FlagManager|MockObject
     */
    private $flagManagerMock;

    /**
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @var DirectoryList|MockObject
     */
    private $directoryListMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->stageConfigMock = $this->getMockBuilder(BuildInterface::class)
            ->getMockForAbstractClass();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->flagManagerMock = $this->createMock(FlagManager::class);
        $this->fileMock = $this->getMockBuilder(File::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->directoryListMock = $this->getMockBuilder(DirectoryList::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->directoryListMock->method('getGeneratedCode')
            ->willReturn('generated_code');

        $this->directoryListMock->method('getGeneratedMetadata')
            ->willReturn('generated_metadata');

        $this->step = new PreBuild(
            $this->stageConfigMock,
            $this->loggerMock,
            $this->flagManagerMock,
            $this->fileMock,
            $this->directoryListMock
        );
    }

    /**
     * @param string $verbosity
     * @param string $expectedVerbosity
     * @dataProvider executeVerbosityDataProvider
     */
    public function testExecuteVerbosity(string $verbosity, string $expectedVerbosity)
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(BuildInterface::VAR_VERBOSE_COMMANDS)
            ->willReturn($verbosity);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Verbosity level is ' . $expectedVerbosity);
        $this->flagManagerMock->expects($this->once())
            ->method('delete')
            ->with(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD);

        $this->step->execute();
    }

    /**
     * @return array
     */
    public function executeVerbosityDataProvider(): array
    {
        return [
            'verbosity very' => ['input' => ' -vvv', 'output' => ' -vvv'],
            'verbosity none' => ['input' => '',      'output' => 'not set'],
        ];
    }

    /**
     * @param bool $istExists
     * @param int $callCount
     * @dataProvider executeClearDirectoriesDataProvider
     */
    public function testExecuteClearDirectories(bool $isExists, int $callCount)
    {
        $generatedCode     = 'generated_code';
        $generatedMetadata = 'generated_metadata';

        $this->fileMock->expects($this->exactly($callCount))
            ->method('clearDirectory')
            ->withConsecutive(
                [$generatedCode],
                [$generatedMetadata]
            )
            ->willReturn(true);

        $this->fileMock->expects($this->exactly(2))
            ->method('isExists')
            ->willReturnMap([
                [$generatedCode, $isExists],
                [$generatedMetadata, $isExists],
            ]);

        $this->step->execute();
    }

    /**
     * @return array
     */
    public function executeClearDirectoriesDataProvider(): array
    {
        return [
            ['isExist' => true, 'clearDirectories' => 2],
            ['isExist' => false, 'clearDirectories' => 0],
        ];
    }

    public function testExecuteWithException()
    {
        $exceptionCode = 111;
        $exceptionMsg = 'Error message';

        $this->expectException(StepException::class);
        $this->expectExceptionMessage($exceptionMsg);
        $this->expectExceptionCode($exceptionCode);

        $this->flagManagerMock->expects($this->once())
            ->method('delete')
            ->willThrowException(new GenericException($exceptionMsg, $exceptionCode));

        $this->step->execute();
    }
}
