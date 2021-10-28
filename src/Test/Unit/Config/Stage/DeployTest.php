<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Stage;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\ConfigException;
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
    protected function setUp(): void
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
     *
     * @throws ConfigException
     */
    public function testGet(string $name, $expectedValue, array $mergedConfig, array $schema = null): void
    {
        $this->mergedConfigMock->expects($this->once())
            ->method('get')
            ->willReturn($mergedConfig);

        if ($schema !== null) {
            $this->schemaMock->expects($this->once())
                ->method('getVariables')
                ->willReturn($schema);
        } else {
            $this->schemaMock->expects($this->never())
                ->method('getVariables');
        }

        $this->assertEquals($expectedValue, $this->deployConfig->get($name));
    }

    /**
     * @return array
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
     * @throws ConfigException
     */
    public function testGetConfigNotDefined(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Config NO_EXISTS_CONFIG was not defined');
        $this->expectExceptionCode(Error::DEPLOY_CONFIG_NOT_DEFINED);

        $this->mergedConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([]);

        $this->deployConfig->get('NO_EXISTS_CONFIG');
    }

    /**
     * @throws ConfigException
     */
    public function testGetWithMergedConfigException(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Some error');

        $this->mergedConfigMock->expects($this->once())
            ->method('get')
            ->willThrowException(new ConfigException('Some error'));

        $this->deployConfig->get(Deploy::VAR_SCD_STRATEGY);
    }
}
