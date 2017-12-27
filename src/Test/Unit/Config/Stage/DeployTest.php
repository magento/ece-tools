<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Stage;

use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Config\Environment\Reader as EnvironmentReader;
use Magento\MagentoCloud\Config\Environment as EnvironmentConfig;
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
            ->willReturn($envConfig);
        $this->environmentConfigMock->expects($this->any())
            ->method('getVariables')
            ->willReturn($envVarConfig);

        $this->assertSame($expectedValue, $this->config->get($name));
    }

    /**
     * @return array
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
        ];
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Config value was not defined.
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
