<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Stage;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\Deploy\EnvironmentConfig;
use Magento\MagentoCloud\Config\Environment\Reader as EnvironmentReader;
use Magento\MagentoCloud\Config\Stage\Deploy;
use Magento\MagentoCloud\Config\StageConfigInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class DeployTest extends TestCase
{
    /**
     * @var Deploy
     */
    private $config;

    /**
     * @var EnvironmentReader|Mock
     */
    private $environmentReaderMock;

    /**
     * @var EnvironmentConfig|Mock
     */
    private $environmentConfigMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentReaderMock = $this->createMock(EnvironmentReader::class);
        $this->environmentConfigMock = $this->getMockBuilder(EnvironmentConfig::class)
            ->setMethods(array('getVariables'))
            ->setConstructorArgs([
                $this->createMock('\Psr\Log\LoggerInterface'),
                $this->createMock('\Magento\MagentoCloud\Filesystem\Driver\File'),
                $this->createMock('\Magento\MagentoCloud\Filesystem\DirectoryList'),
                $this->createMock('\Magento\MagentoCloud\Filesystem\Flag\Manager'),
            ])->getMock();
        $this->config = new Deploy(
            $this->environmentReaderMock,
            $this->environmentConfigMock
        );
    }

    /**
     * @param string $name
     * @param array $envConfig
     * @param array $envVarConfig
     * @param mixed $expectedValue
     * @dataProvider getDataProvider
     */
    public function testGet(string $name, array $envConfig, array $envVarConfig, $expectedValue)
    {
        $this->environmentReaderMock->expects($this->any())
            ->method('read')
            ->willReturn([Deploy::SECTION_STAGE => $envConfig]);
        $this->environmentConfigMock->expects($this->any())
            ->method('getAll')
            ->willReturn($envVarConfig);

        $this->assertSame($expectedValue, $this->config->get($name));
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getDataProvider(): array
    {
        return [
            'default strategy' => [
                Deploy::VAR_SCD_STRATEGY,
                [],
                [],
                '',
            ],
            'env configured strategy' => [
                Deploy::VAR_SCD_STRATEGY,
                [
                    Deploy::STAGE_GLOBAL => [],
                    Deploy::STAGE_DEPLOY => [
                        Deploy::VAR_SCD_STRATEGY => 'simple',
                    ],
                ],
                [],
                'simple',
            ],
            'global env strategy' => [
                Deploy::VAR_SCD_STRATEGY,
                [
                    Deploy::STAGE_GLOBAL => [
                        Deploy::VAR_SCD_STRATEGY => 'simple',
                    ],
                    Deploy::STAGE_DEPLOY => [],
                ],
                [],
                'simple',
            ],
            'default strategy with parameter' => [
                Deploy::VAR_SCD_STRATEGY,
                [
                    Deploy::STAGE_GLOBAL => [],
                    Deploy::STAGE_DEPLOY => [],
                ],
                [],
                '',
            ],
            'env var configured strategy' => [
                Deploy::VAR_SCD_STRATEGY,
                [
                    Deploy::STAGE_GLOBAL => [],
                    Deploy::STAGE_DEPLOY => [
                        Deploy::VAR_SCD_STRATEGY => 'simple',
                    ],
                ],
                [
                    Deploy::VAR_SCD_STRATEGY => 'quick',
                ],
                'quick',
            ],
            'json value' => [
                Deploy::VAR_QUEUE_CONFIGURATION,
                [
                    Deploy::STAGE_DEPLOY => [
                        Deploy::VAR_QUEUE_CONFIGURATION => '{"SOME_CONFIG": "some value"}',
                    ],
                ],
                [],
                ['SOME_CONFIG' => 'some value'],
            ],
            'wrong json value' => [
                Deploy::VAR_QUEUE_CONFIGURATION,
                [
                    Deploy::STAGE_DEPLOY => [
                        Deploy::VAR_QUEUE_CONFIGURATION => '{"SOME_CONFIG": "some value',
                    ],
                ],
                [],
                '{"SOME_CONFIG": "some value',
            ],
            'disabled flow 1' => [
                Deploy::VAR_UPDATE_URLS,
                [],
                [
                    Deploy::VAR_UPDATE_URLS => false,
                ],
                false,
            ],
            'deprecated do deploy scd' => [
                Deploy::VAR_SKIP_SCD,
                [],
                [
                    Deploy::VAR_SKIP_SCD => false,
                ],
                false,
            ],
            'do deploy scd' => [
                Deploy::VAR_SKIP_SCD,
                [],
                [],
                false,
            ],
            'verbosity deprecated' => [
                Deploy::VAR_VERBOSE_COMMANDS,
                [],
                [
                    Deploy::VAR_VERBOSE_COMMANDS => '-vvv',
                ],
                '-vvv',
            ],
            'verbosity disabled deprecated' => [
                Deploy::VAR_VERBOSE_COMMANDS,
                [],
                [],
                '',
            ],
            'threads default' => [
                Deploy::VAR_SCD_THREADS,
                [],
                [],
                1,
            ],
            'scd strategy default' => [
                Deploy::VAR_SCD_STRATEGY,
                [],
                [],
                ''
            ],
            'exclude themes deprecated' => [
                Deploy::VAR_SCD_EXCLUDE_THEMES,
                [
                    Deploy::VAR_SCD_EXCLUDE_THEMES => 'some theme'
                ],
                [
                    Deploy::VAR_SCD_EXCLUDE_THEMES => 'some theme 2',
                ],
                'some theme 2',
            ],
            'exclude themes' => [
                Deploy::VAR_SCD_EXCLUDE_THEMES,
                [],
                [
                    Deploy::VAR_SCD_EXCLUDE_THEMES => 'some theme 2',
                ],
                'some theme 2',
            ],
            'redis_use_slave_default' => [
                Deploy::VAR_REDIS_USE_SLAVE_CONNECTION,
                [],
                [],
                false,
            ],
            'redis_use_slave_true' => [
                Deploy::VAR_REDIS_USE_SLAVE_CONNECTION,
                [],
                [
                    Deploy::VAR_REDIS_USE_SLAVE_CONNECTION => true
                ],
                true,
            ],
            'default slave connection' => [
                Deploy::VAR_MYSQL_USE_SLAVE_CONNECTION,
                [],
                [],
                false,
            ],
            'use slave connection' => [
                Deploy::VAR_MYSQL_USE_SLAVE_CONNECTION,
                [],
                [
                    Deploy::VAR_MYSQL_USE_SLAVE_CONNECTION => true
                ],
                true,
            ],
        ];
    }

    /**
     * @param string $name
     * @param array $envConfig Deploy config from .magento.env.yaml
     * @param array $envVarConfig Cloud variables configuration
     * @param array $rawEnv Raw $_ENV configuration
     * @param int $expectedValue
     * @dataProvider getDeprecatedScdThreadsDataProvider
     */
    public function testGetDeprecatedScdThreads(
        string $name,
        array $envConfig,
        array $envVarConfig,
        array $rawEnv,
        int $expectedValue
    ) {
        $this->environmentReaderMock->expects($this->any())
            ->method('read')
            ->willReturn([Deploy::SECTION_STAGE => $envConfig]);
        $this->environmentConfigMock->expects($this->once())
            ->method('getAll')
            ->willReturn($envVarConfig);
        $_ENV = $rawEnv;

        $this->assertSame($expectedValue, $this->config->get($name));
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getDeprecatedScdThreadsDataProvider(): array
    {
        return [
            'threads' => [
                Deploy::VAR_SCD_THREADS,
                [],
                [
                    Deploy::VAR_SCD_THREADS => 4,
                ],
                [],
                4,
            ],
            'threads mode none' => [
                Deploy::VAR_SCD_THREADS,
                [],
                [],
                [
                    'MAGENTO_CLOUD_MODE' => '',
                ],
                1,
            ],
            'threads mode enterprise' => [
                Deploy::VAR_SCD_THREADS,
                [],
                [],
                [
                    'MAGENTO_CLOUD_MODE' => Environment::CLOUD_MODE_ENTERPRISE,
                ],
                3,
            ],
            'threads mode enterprise and magento cloud variable' => [
                Deploy::VAR_SCD_THREADS,
                [],
                [
                    Deploy::VAR_SCD_THREADS => 5,
                ],
                [
                    'MAGENTO_CLOUD_MODE' => Environment::CLOUD_MODE_ENTERPRISE,
                ],
                5,
            ],
            'mode enterprise with global and deploy scd_threads in .magento.env.yaml' => [
                Deploy::VAR_SCD_THREADS,
                [
                    StageConfigInterface::STAGE_GLOBAL => [
                        StageConfigInterface::VAR_SCD_THREADS => 5
                    ],
                    StageConfigInterface::STAGE_DEPLOY => [
                        StageConfigInterface::VAR_SCD_THREADS => 4
                    ],
                ],
                [],
                [
                    'MAGENTO_CLOUD_MODE' => Environment::CLOUD_MODE_ENTERPRISE,
                ],
                4,
            ],
            'threads mode enterprise with global scd_threads in .magento.env.yaml' => [
                Deploy::VAR_SCD_THREADS,
                [
                    StageConfigInterface::STAGE_GLOBAL => [
                        StageConfigInterface::VAR_SCD_THREADS => 5
                    ],
                ],
                [],
                [
                    'MAGENTO_CLOUD_MODE' => Environment::CLOUD_MODE_ENTERPRISE,
                ],
                5,
            ],
            'threads mode enterprise with global and deploy scd_threads in .magento.env.yaml and cloud variable' => [
                Deploy::VAR_SCD_THREADS,
                [
                    StageConfigInterface::STAGE_GLOBAL => [
                        StageConfigInterface::VAR_SCD_THREADS => 5
                    ],
                    StageConfigInterface::STAGE_DEPLOY => [
                        StageConfigInterface::VAR_SCD_THREADS => 4
                    ],
                ],
                [
                    Deploy::VAR_SCD_THREADS => 7,
                ],
                [
                    'MAGENTO_CLOUD_MODE' => Environment::CLOUD_MODE_ENTERPRISE,
                ],
                7,
            ],
        ];
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Config NOT_EXISTS_VALUE was not defined.
     */
    public function testNotExists()
    {
        $this->environmentReaderMock->expects($this->any())
            ->method('read')
            ->willReturn([]);
        $this->environmentConfigMock->expects($this->any())
            ->method('getAll')
            ->willReturn([]);

        $this->config->get('NOT_EXISTS_VALUE');
    }
}
