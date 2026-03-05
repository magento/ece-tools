<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy\PreDeploy;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Config\Magento\Env\ReaderInterface as ConfigReader;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Step\Deploy\PreDeploy\CheckState;
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
class CheckStateTest extends TestCase
{
    /**
     * @var ConfigReader|MockObject
     */
    private $configReaderMock;

    /**
     * @var FlagManager|MockObject
     */
    private $flagManagerMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var CheckState
     */
    private $checkState;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->configReaderMock = $this->createMock(ConfigReader::class);
        $this->flagManagerMock  = $this->createMock(FlagManager::class);
        $this->loggerMock       = $this->createMock(LoggerInterface::class);

        $this->checkState = new CheckState(
            $this->configReaderMock,
            $this->flagManagerMock,
            $this->loggerMock
        );
    }

    /**
     * Test execute with empty file method.
     *
     * @param array $config
     * @dataProvider executeWithEmptyFileDataProvider
     * @return void
     * @throws StepException
     */
    #[DataProvider('executeWithEmptyFileDataProvider')]
    public function testExecuteWithEmptyFile($config)
    {
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn($config);

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with(sprintf('Set "%s" flag', FlagManager::FLAG_ENV_FILE_ABSENCE));
        $this->flagManagerMock->expects($this->once())
            ->method('set')
            ->with(FlagManager::FLAG_ENV_FILE_ABSENCE);

        $this->checkState->execute();
    }

    /**
     * Data provider for testExecuteWithEmptyFile test
     *
     * @return array
     */
    public static function executeWithEmptyFileDataProvider(): array
    {
        return [
            [
                [],
            ],
            [
                [
                    'cache_types' => '',
                ],
            ],
            [
                [
                    'cache_types' => [
                        'type_1' => 1,
                    ],
                ],
            ],
        ];
    }

    /**
     * Data provider for testExecuteWithFullOfDataFile test.
     *
     * @param $config
     * @dataProvider executeWithFullOfDataFileDataProvider
     * @return void
     * @throws StepException
     */
    #[DataProvider('executeWithFullOfDataFileDataProvider')]
    public function testExecuteWithFullOfDataFile(array $config): void
    {
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn($config);

        $this->loggerMock->expects($this->never())
            ->method('info');
        $this->flagManagerMock->expects($this->never())
            ->method('set');

        $this->checkState->execute();
    }

    /**
     * Test execute with exception method.
     *
     * @return void
     * @throws StepException
     */
    public function testExecuteWithException(): void
    {
        $eCode = 111;
        $eMessage = 'Exception message';
        $exception = new GenericException($eMessage, $eCode);
        $this->expectExceptionObject(new StepException($eMessage, $eCode, $exception));
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willThrowException($exception);

        $this->checkState->execute();
    }

    /**
     * Data provider for testExecuteWithFullOfDataFile test
     *
     * @return array
     */
    public static function executeWithFullOfDataFileDataProvider(): array
    {
        return [
            [
                ['cache_types' => '', 'other_data' => []]
            ],
            [
                ['other_data' => []]
            ],
        ];
    }
}
