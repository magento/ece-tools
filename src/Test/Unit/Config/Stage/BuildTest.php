<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Stage;

use Magento\MagentoCloud\Config\Stage\Build;
use Magento\MagentoCloud\Config\StageConfigInterface;
use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Config\Environment\Reader as EnvironmentReader;
use Magento\MagentoCloud\Config\Build\Reader as BuildReader;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class BuildTest extends TestCase
{
    /**
     * @var Build
     */
    private $config;

    /**
     * @var EnvironmentReader|Mock
     */
    private $environmentReaderMock;

    /**
     * @var BuildReader|Mock
     */
    private $buildReaderMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentReaderMock = $this->createMock(EnvironmentReader::class);
        $this->buildReaderMock = $this->createMock(BuildReader::class);

        $this->config = new Build(
            $this->environmentReaderMock,
            $this->buildReaderMock
        );
    }

    /**
     * @param string $name
     * @param array $envConfig
     * @param array $buildConfig
     * @param mixed $expectedValue
     * @dataProvider getDataProvider
     */
    public function testGet(string $name, array $envConfig, array $buildConfig, $expectedValue)
    {
        $this->environmentReaderMock->expects($this->once())
            ->method('read')
            ->willReturn($envConfig);
        $this->buildReaderMock->expects($this->once())
            ->method('read')
            ->willReturn($buildConfig);

        $this->assertSame($expectedValue, $this->config->get($name));
    }

    /**
     * @return array
     */
    public function getDataProvider(): array
    {
        return [
            'default strategy' => [
                Build::VAR_SCD_STRATEGY,
                [],
                [],
                '',
            ],
            'env configured strategy' => [
                Build::VAR_SCD_STRATEGY,
                [
                    StageConfigInterface::STAGE_GLOBAL => [],
                    StageConfigInterface::STAGE_BUILD => [
                        Build::VAR_SCD_STRATEGY => 'simple',
                    ],
                ],
                [],
                'simple',
            ],
            'global env strategy' => [
                Build::VAR_SCD_STRATEGY,
                [
                    StageConfigInterface::STAGE_GLOBAL => [
                        Build::VAR_SCD_STRATEGY => 'simple',
                    ],
                    StageConfigInterface::STAGE_BUILD => [],
                ],
                [],
                'simple',
            ],
            'default strategy with parameter' => [
                Build::VAR_SCD_STRATEGY,
                [
                    StageConfigInterface::STAGE_GLOBAL => [],
                    StageConfigInterface::STAGE_BUILD => [],
                ],
                [],
                '',
            ],
            'build configured strategy' => [
                Build::VAR_SCD_STRATEGY,
                [
                    StageConfigInterface::STAGE_GLOBAL => [],
                    StageConfigInterface::STAGE_BUILD => [
                        Build::VAR_SCD_STRATEGY => 'simple',
                    ],
                ],
                [
                    Build::VAR_SCD_STRATEGY => 'quick',
                ],
                'quick',
            ],
        ];
    }
}
