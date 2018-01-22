<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Stage;

use Magento\MagentoCloud\Config\Environment as EnvironmentConfig;
use Magento\MagentoCloud\Config\Environment\Reader as EnvironmentReader;
use Magento\MagentoCloud\Config\Stage\Deploy;
use Magento\MagentoCloud\Config\ScdStrategyChecker;
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
     * @var ScdStrategyChecker|Mock
     */
    private $scdStrategyCheckerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentReaderMock = $this->createMock(EnvironmentReader::class);
        $this->environmentConfigMock = $this->createMock(EnvironmentConfig::class);
        $this->scdStrategyCheckerMock = $this->createMock(ScdStrategyChecker::class);

        $this->config = new Deploy(
            $this->environmentReaderMock,
            $this->environmentConfigMock,
            $this->scdStrategyCheckerMock
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
                Deploy::VAR_REDIS_SESSION_DISABLE_LOCKING,
                [],
                [
                    Deploy::VAR_REDIS_SESSION_DISABLE_LOCKING => EnvironmentConfig::VAL_DISABLED,
                ],
                false,
            ],
            'disabled flow 2' => [
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
            'allowed strategies value' => [
                Deploy::VAR_SCD_ALLOWED_STRATEGIES,
                [],
                [
                    Deploy::VAR_SCD_ALLOWED_STRATEGIES => ['default']
                ],
                ['default']
            ],
            'strategy default' => [
                Deploy::VAR_SCD_STRATEGY,
                [],
                [],
                ''
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
