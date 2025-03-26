<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Config\Magento\Env\ReaderInterface as ConfigReader;
use Magento\MagentoCloud\Config\Magento\Env\WriterInterface as ConfigWriter;
use Magento\MagentoCloud\Config\Environment;
use Illuminate\Config\Repository;
use Magento\MagentoCloud\Config\RepositoryFactory;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Step\Deploy\InstallUpdate\ConfigUpdate\CronConsumersRunner;
use Magento\MagentoCloud\Step\StepException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CronConsumersRunnerTest extends TestCase
{
    /**
     * @var CronConsumersRunner
     */
    private $cronConsumersRunner;

    /**
     * @var Environment|MockObject
     */
    private $environmentMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var ConfigReader|MockObject
     */
    private $configReaderMock;

    /**
     * @var ConfigWriter|MockObject
     */
    private $configWriterMock;

    /**
     * @var DeployInterface|MockObject
     */
    private $stageConfigMock;

    /**
     * @var MagentoVersion|MockObject
     */
    private $magentoVersionMock;

    /**
     * @var RepositoryFactory|MockObject
     */
    private $repositoryFactoryMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->configReaderMock = $this->createMock(ConfigReader::class);
        $this->configWriterMock = $this->createMock(ConfigWriter::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);
        $this->repositoryFactoryMock = $this->createMock(RepositoryFactory::class);

        $this->cronConsumersRunner = new CronConsumersRunner(
            $this->environmentMock,
            $this->configReaderMock,
            $this->configWriterMock,
            $this->loggerMock,
            $this->stageConfigMock,
            $this->magentoVersionMock,
            $this->repositoryFactoryMock
        );
    }

    /**
     * @param array $config
     * @param array $configFromVariable
     * @param array $expectedResult
     *
     * @throws StepException
     *
     * @dataProvider executeDataProvider
     */
    public function testExecute(array $config, array $configFromVariable, array $expectedResult): void
    {
        $this->magentoVersionMock->method('isGreaterOrEqual')
            ->willReturn(true);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating env.php cron consumers runner configuration.');
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn($config);
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_CRON_CONSUMERS_RUNNER)
            ->willReturn($configFromVariable);
        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with($expectedResult);
        $this->repositoryFactoryMock->expects($this->once())
            ->method('create')
            ->with($configFromVariable)
            ->willReturn(new Repository($configFromVariable));

        $this->cronConsumersRunner->execute();
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function executeDataProvider(): array
    {
        return [
            [
                'config' => [],
                'configFromVariable' => [],
                'expectedResult' => [
                    'cron_consumers_runner' => [
                        'cron_run' => false,
                        'max_messages' => 10000,
                        'consumers' => [],
                        'multiple_processes' => [],
                    ],
                ],
            ],
            [
                'config' => [
                    'someConfig' => 'someValue',
                    'cron_consumers_runner' => [
                        'cron_run' => true,
                        'max_messages' => 6000,
                        'consumers' => ['test'],
                        'multiple_processes' => [],
                    ],
                ],
                'configFromVariable' => [],
                'expectedResult' => [
                    'someConfig' => 'someValue',
                    'cron_consumers_runner' => [
                        'cron_run' => false,
                        'max_messages' => 10000,
                        'consumers' => [],
                        'multiple_processes' => [],
                    ],
                ],
            ],
            [
                'config' => [
                    'someConfig' => 'someValue',
                    'cron_consumers_runner' => [
                        'cron_run' => true,
                        'max_messages' => 6000,
                        'consumers' => ['test'],
                        'multiple_processes' => ['test' => 2],
                    ],
                ],
                'configFromVariable' => ['cron_run' => 'false'],
                'expectedResult' => [
                    'someConfig' => 'someValue',
                    'cron_consumers_runner' => [
                        'cron_run' => false,
                        'max_messages' => 10000,
                        'consumers' => [],
                        'multiple_processes' => [],
                    ],
                ],
            ],
            [
                'config' => [
                    'someConfig' => 'someValue',
                    'cron_consumers_runner' => [
                        'cron_run' => true,
                        'max_messages' => 6000,
                        'consumers' => ['test'],
                        'multiple_processes' => ['test' => 2],
                    ],
                ],
                'configFromVariable' => ['cron_run' => true],
                'expectedResult' => [
                    'someConfig' => 'someValue',
                    'cron_consumers_runner' => [
                        'cron_run' => true,
                        'max_messages' => 10000,
                        'consumers' => [],
                        'multiple_processes' => [],
                    ],
                ],
            ],
            [
                'config' => [
                    'someConfig' => 'someValue',
                    'cron_consumers_runner' => [
                        'cron_run' => true,
                        'max_messages' => 6000,
                        'consumers' => ['test'],
                        'multiple_processes' => ['test' => 2],
                    ],
                ],
                'configFromVariable' => [
                    'cron_run' => true,
                    'max_messages' => 200,
                    'consumers' => ['test2', 'test3'],
                    'multiple_processes' => ['test' => 1],
                ],
                'expectedResult' => [
                    'someConfig' => 'someValue',
                    'cron_consumers_runner' => [
                        'cron_run' => true,
                        'max_messages' => 200,
                        'consumers' => ['test2', 'test3'],
                        'multiple_processes' => ['test' => 1],
                    ],
                ],
            ],
            [
                'config' => [
                    'someConfig' => 'someValue',
                    'cron_consumers_runner' => [
                        'cron_run' => true,
                        'max_messages' => 6000,
                        'consumers' => ['test'],
                        'multiple_processes' => ['test' => 2],
                    ],
                ],
                'configFromVariable' => [
                    'cron_run' => 'true',
                    'max_messages' => 200,
                    'consumers' => ['test2', 'test3'],
                    'multiple_processes' => ['test' => 4],
                ],
                'expectedResult' => [
                    'someConfig' => 'someValue',
                    'cron_consumers_runner' => [
                        'cron_run' => false,
                        'max_messages' => 200,
                        'consumers' => ['test2', 'test3'],
                        'multiple_processes' => ['test' => 4],
                    ],
                ],
            ],
        ];
    }

    /**
     * @throws StepException
     */
    public function testSkipExecute(): void
    {
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.2')
            ->willReturn(false);
        $this->configReaderMock->expects($this->never())
            ->method('read');
        $this->stageConfigMock->expects($this->never())
            ->method('get');
        $this->configWriterMock->expects($this->never())
            ->method('create');

        $this->cronConsumersRunner->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithGenericException()
    {
        $exceptionMsg = 'Error';
        $exceptionCode = 111;

        $this->expectException(StepException::class);
        $this->expectExceptionMessage($exceptionMsg);
        $this->expectExceptionCode($exceptionCode);

        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->willThrowException(new GenericException($exceptionMsg, $exceptionCode));

        $this->cronConsumersRunner->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithFileSystemExceptionInCreate()
    {
        $exceptionMsg = 'Some error';
        $exceptionCode = 11111;
        $this->expectException(StepException::class);
        $this->expectExceptionCode(Error::DEPLOY_ENV_PHP_IS_NOT_WRITABLE);
        $this->expectExceptionMessage($exceptionMsg);

        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.2')
            ->willReturn(true);

        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([]);

        $this->repositoryFactoryMock->expects($this->once())
            ->method('create')
            ->with([])
            ->willReturn(new Repository([]));

        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_CRON_CONSUMERS_RUNNER)
            ->willReturn([]);

        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->willThrowException(new FileSystemException($exceptionMsg, $exceptionCode));

        $this->cronConsumersRunner->execute();
    }
}
