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
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
#[AllowMockObjectsWithoutExpectations]
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
        $this->stageConfigMock = $this->createMock(BuildInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->flagManagerMock = $this->createMock(FlagManager::class);
        $this->fileMock = $this->createMock(File::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);

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
     * Test execute verbosity method.
     *
     * @param string $verbosity
     * @param string $expectedVerbosity
     * @dataProvider executeVerbosityDataProvider
     * @return void
     * @throws \ReflectionException
     */
    #[DataProvider('executeVerbosityDataProvider')]
    public function testExecuteVerbosity(string $verbosity, string $expectedVerbosity): void
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
     * Data provider for execute verbosity method.
     *
     * @return array
     */
    public static function executeVerbosityDataProvider(): array
    {
        return [
            'verbosity very' => [
                'verbosity'         => ' -vvv',
                'expectedVerbosity' => ' -vvv',
            ],
            'verbosity none' => [
                'verbosity'         => '',
                'expectedVerbosity' => 'not set',
            ],
        ];
    }

    /**
     * Test execute clear directories method.
     *
     * @param bool $istExists
     * @param int $callCount
     * @dataProvider executeClearDirectoriesDataProvider
     * @return void
     * @throws \ReflectionException
     */
    #[DataProvider('executeClearDirectoriesDataProvider')]
    public function testExecuteClearDirectories(bool $isExists, int $callCount)
    {
        $generatedCode     = 'generated_code';
        $generatedMetadata = 'generated_metadata';

        $this->fileMock->expects($this->exactly($callCount))
            ->method('clearDirectory')
            // withConsecutive() alternative.
            ->willReturnCallback(fn($param) => match ([$param]) {
                [$generatedCode] => true,
                [$generatedMetadata] => true
            });

        $this->fileMock->expects($this->exactly(2))
            ->method('isExists')
            ->willReturnMap([
                [$generatedCode, $isExists],
                [$generatedMetadata, $isExists],
            ]);

        $this->step->execute();
    }

    /**
     * Data provider for execute clear directories method.
     *
     * @return array
     */
    public static function executeClearDirectoriesDataProvider(): array
    {
        return [
            [
                'isExists'  => true,
                'callCount' => 2,
            ],
            [
                'isExists'  => false,
                'callCount' => 0,
            ],
        ];
    }

    /**
     * Test execute with exception method.
     *
     * @return void
     * @throws \ReflectionException
     */
    public function testExecuteWithException(): void
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
