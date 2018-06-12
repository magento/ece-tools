<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\Deploy\Reader as ConfigReader;
use Magento\MagentoCloud\Config\Deploy\Writer as ConfigWriter;
use Magento\MagentoCloud\Config\Environment;
use Illuminate\Config\Repository;
use Magento\MagentoCloud\Config\RepositoryFactory;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\CronConsumersRunner;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class CronConsumersRunnerTest extends TestCase
{
    /**
     * @var CronConsumersRunner
     */
    private $cronConsumersRunner;

    /**
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var ConfigReader|Mock
     */
    private $configReaderMock;

    /**
     * @var ConfigWriter|Mock
     */
    private $configWriterMock;

    /**
     * @var DeployInterface|Mock
     */
    private $stageConfigMock;

    /**
     * @var MagentoVersion|Mock
     */
    private $magentoVersionMock;

    /**
     * @var RepositoryFactory|Mock
     */
    private $repositoryFactoryMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
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
     * @dataProvider executeDataProvider
     */
    public function testExecute(array $config, array $configFromVariable, array $expectedResult)
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
                    ],
                ],
                'configFromVariable' => [],
                'expectedResult' => [
                    'someConfig' => 'someValue',
                    'cron_consumers_runner' => [
                        'cron_run' => false,
                        'max_messages' => 10000,
                        'consumers' => [],
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
                    ],
                ],
                'configFromVariable' => ['cron_run' => 'false'],
                'expectedResult' => [
                    'someConfig' => 'someValue',
                    'cron_consumers_runner' => [
                        'cron_run' => false,
                        'max_messages' => 10000,
                        'consumers' => [],
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
                    ],
                ],
                'configFromVariable' => ['cron_run' => true],
                'expectedResult' => [
                    'someConfig' => 'someValue',
                    'cron_consumers_runner' => [
                        'cron_run' => true,
                        'max_messages' => 10000,
                        'consumers' => [],
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
                    ],
                ],
                'configFromVariable' => [
                    'cron_run' => true,
                    'max_messages' => 200,
                    'consumers' => ['test2', 'test3'],
                ],
                'expectedResult' => [
                    'someConfig' => 'someValue',
                    'cron_consumers_runner' => [
                        'cron_run' => true,
                        'max_messages' => 200,
                        'consumers' => ['test2', 'test3'],
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
                    ],
                ],
                'configFromVariable' => [
                    'cron_run' => 'true',
                    'max_messages' => 200,
                    'consumers' => ['test2', 'test3'],
                ],
                'expectedResult' => [
                    'someConfig' => 'someValue',
                    'cron_consumers_runner' => [
                        'cron_run' => false,
                        'max_messages' => 200,
                        'consumers' => ['test2', 'test3'],
                    ],
                ],
            ],
        ];
    }

    public function testSkipExecute()
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
}
