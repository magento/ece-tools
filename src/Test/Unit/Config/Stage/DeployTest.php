<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Stage;

use Magento\MagentoCloud\Config\Schema;
use Magento\MagentoCloud\Config\Stage\Deploy;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\StageConfigInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class DeployTest extends TestCase
{
    /**
     * @var Deploy
     */
    private $deployConfig;

    /**
     * @var Deploy\MergedConfig|MockObject
     */
    private $mergedConfigMock;

    /**
     * @var Schema|MockObject
     */
    private $schemaMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->mergedConfigMock = $this->createMock(Deploy\MergedConfig::class);
        $this->schemaMock = $this->createMock(Schema::class);

        $this->deployConfig = new Deploy(
            $this->mergedConfigMock,
            $this->schemaMock
        );
    }

    /**
     * @param string $name
     * @param mixed $expectedValue
     * @param array $mergedConfig
     * @param array|null $schema
     * @dataProvider getDataProvider
     */
    public function testGet(string $name, $expectedValue, array $mergedConfig, array $schema = null)
    {
        $this->mergedConfigMock->expects($this->once())
            ->method('get')
            ->willReturn($mergedConfig);

        if ($schema !== null) {
            $this->schemaMock->expects($this->once())
                ->method('getSchema')
                ->willReturn($schema);
        } else {
            $this->schemaMock->expects($this->never())
                ->method('getSchema');
        }

        $this->assertEquals($expectedValue, $this->deployConfig->get($name));
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getDataProvider(): array
    {
        return [
            'integer config value' => [
                Deploy::VAR_SCD_STRATEGY,
                3,
                [Deploy::VAR_SCD_STRATEGY => 3],
            ],
            'array config value' => [
                Deploy::VAR_SESSION_CONFIGURATION,
                [
                    'save' => 'redis'
                ],
                [
                    Deploy::VAR_SESSION_CONFIGURATION => ['save' => 'redis']
                ],
            ],
            'null config value' => [
                Deploy::VAR_SCD_MAX_EXEC_TIME,
                null,
                [Deploy::VAR_SCD_MAX_EXEC_TIME => null],
            ],
            'string value not a json' => [
                Deploy::VAR_SCD_STRATEGY,
                'compact',
                [
                    Deploy::VAR_SCD_STRATEGY => 'compact'
                ],
                [
                    DeployInterface::VAR_SCD_STRATEGY => [
                        Schema::SCHEMA_TYPE => ['string'],
                    ],
                ],
            ],
            'string value wrong json format and not array-type config' => [
                Deploy::VAR_SCD_STRATEGY,
                '{compact}',
                [
                    Deploy::VAR_SCD_STRATEGY => '{compact}'
                ],
                [
                    DeployInterface::VAR_SCD_STRATEGY => [
                        Schema::SCHEMA_TYPE => ['string'],
                    ],
                ],
            ],
            'correct json format value and array-type config' => [
                Deploy::VAR_SESSION_CONFIGURATION,
                [
                    'save' => 'redis',
                    'redis' => [
                        'host' => 'localhost',
                        'port' => 6372,
                        'database' => 25
                    ],
                ],
                [
                    Deploy::VAR_SESSION_CONFIGURATION =>
                        '{"save": "redis","redis": {"host": "localhost","port": "6372","database": 25}}'
                ],
                [
                    DeployInterface::VAR_SESSION_CONFIGURATION => [
                        Schema::SCHEMA_TYPE => ['array'],
                    ],
                ],
            ],
            'wrong json format value and array-type config (default value usage)' => [
                Deploy::VAR_SESSION_CONFIGURATION,
                ['default' => 'value'],
                [
                    Deploy::VAR_SESSION_CONFIGURATION =>
                        '{"save": "redis","redis": {"host": "localhost","port": "6372","database": 25,}}'
                ],
                [
                    DeployInterface::VAR_SESSION_CONFIGURATION => [
                        Schema::SCHEMA_TYPE => ['array'],
                        Schema::SCHEMA_DEFAULT_VALUE => [
                            StageConfigInterface::STAGE_DEPLOY => ['default' => 'value'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @expectedExceptionMessage Config NO_EXISTS_CONFIG was not defined
     * @expectedException \RuntimeException
     */
    public function testGetConfigNotDefined()
    {
        $this->mergedConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([]);

        $this->deployConfig->get('NO_EXISTS_CONFIG');
    }

    /**
     * @expectedExceptionMessage Some error
     * @expectedException \RuntimeException
     */
    public function testGetWithMergedConfigException()
    {
        $this->mergedConfigMock->expects($this->once())
            ->method('get')
            ->willThrowException(new \RuntimeException('Some error'));

        $this->deployConfig->get(Deploy::VAR_SCD_STRATEGY);
    }
}
