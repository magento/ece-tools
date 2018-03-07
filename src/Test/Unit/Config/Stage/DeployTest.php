<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Stage;

use Magento\MagentoCloud\Config\Environment as EnvironmentConfig;
use Magento\MagentoCloud\Config\Environment\Reader as EnvironmentReader;
use Magento\MagentoCloud\Config\Stage\Deploy;
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
        $this->environmentConfigMock = $this->createMock(EnvironmentConfig::class);

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
            ->method('getVariables')
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
                    Deploy::VAR_UPDATE_URLS => EnvironmentConfig::VAL_DISABLED,
                ],
                false,
            ],
            'deprecated do deploy scd' => [
                Deploy::VAR_SKIP_SCD,
                [],
                [
                    'DO_DEPLOY_STATIC_CONTENT' => EnvironmentConfig::VAL_DISABLED,
                ],
                true,
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
                    Deploy::VAR_VERBOSE_COMMANDS => EnvironmentConfig::VAL_ENABLED,
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
            'threads deprecated' => [
                Deploy::VAR_SCD_THREADS,
                [],
                [
                    'STATIC_CONTENT_THREADS' => 4,
                ],
                4,
            ],
            'scd strategy default' => [
                Deploy::VAR_SCD_STRATEGY,
                [],
                [],
                ''
            ],
            'exclude themes deprecated' => [
                Deploy::VAR_SCD_EXCLUDE_THEMES,
                [],
                [
                    'STATIC_CONTENT_EXCLUDE_THEMES' => 'some theme',
                    Deploy::VAR_SCD_EXCLUDE_THEMES => 'some theme 2',
                ],
                'some theme',
            ],
            'exclude themes' => [
                Deploy::VAR_SCD_EXCLUDE_THEMES,
                [],
                [
                    Deploy::VAR_SCD_EXCLUDE_THEMES => 'some theme 2',
                ],
                'some theme 2',
            ],
            'default slave connection' => [
                Deploy::VAR_MYSQL_READ_DISTRIBUTION,
                [],
                [],
                false,
            ],
            'use slave connection' => [
                Deploy::VAR_MYSQL_READ_DISTRIBUTION,
                [],
                [
                    Deploy::VAR_MYSQL_READ_DISTRIBUTION => true
                ],
                true,
            ],
        ];
    }

    /**
     * @param string $name
     * @param array $envConfig
     * @param array $envVarConfig
     * @param array $rawEnv
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
        $this->environmentConfigMock->expects($this->any())
            ->method('getVariables')
            ->willReturn($envVarConfig);
        $_ENV = $rawEnv;

        $this->assertSame($expectedValue, $this->config->get($name));
    }

    /**
     * @return array
     */
    public function getDeprecatedScdThreadsDataProvider(): array
    {
        return [
            'threads' => [
                Deploy::VAR_SCD_THREADS,
                [],
                [
                    'STATIC_CONTENT_THREADS' => 4,
                ],
                [],
                4,
            ],
            'threads raw' => [
                Deploy::VAR_SCD_THREADS,
                [],
                [
                    'STATIC_CONTENT_THREADS' => 4,
                ],
                [
                    'STATIC_CONTENT_THREADS' => 3,
                ],
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
                    'MAGENTO_CLOUD_MODE' => EnvironmentConfig::CLOUD_MODE_ENTERPRISE,
                ],
                3,
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
            ->method('getVariables')
            ->willReturn([]);

        $this->config->get('NOT_EXISTS_VALUE');
    }
}
