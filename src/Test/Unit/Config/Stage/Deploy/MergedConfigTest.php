<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Stage\Deploy;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Config\Environment\Reader as EnvironmentReader;
use Magento\MagentoCloud\Config\Schema;
use Magento\MagentoCloud\Config\Stage\Deploy;
use Magento\MagentoCloud\Config\Stage\Deploy\EnvironmentConfig;
use Magento\MagentoCloud\Config\Stage\Deploy\MergedConfig;
use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * @inheritdoc
 */
class MergedConfigTest extends TestCase
{
    /**
     * @var MergedConfig
     */
    private $mergedConfig;

    /**
     * @var EnvironmentReader|MockObject
     */
    private $environmentReaderMock;

    /**
     * @var EnvironmentConfig|MockObject
     */
    private $environmentConfigMock;

    /**
     * @var Schema|MockObject
     */
    private $schemaMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->environmentReaderMock = $this->createMock(EnvironmentReader::class);
        $this->environmentConfigMock = $this->createMock(EnvironmentConfig::class);
        $this->schemaMock = $this->createMock(Schema::class);

        $this->mergedConfig = new MergedConfig(
            $this->environmentReaderMock,
            $this->environmentConfigMock,
            $this->schemaMock
        );
    }

    /**
     * @param array $defaults
     * @param array $envConfig
     * @param array $envVarConfig
     * @param array $expectedConfig
     * @dataProvider getDataProvider
     *
     * @throws ConfigException
     */
    public function testGet(array $defaults, array $envConfig, array $envVarConfig, array $expectedConfig): void
    {
        $this->schemaMock->expects($this->once())
            ->method('getDefaults')
            ->with(StageConfigInterface::STAGE_DEPLOY)
            ->willReturn($defaults);
        $this->environmentReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([StageConfigInterface::SECTION_STAGE => $envConfig]);
        $this->environmentConfigMock->expects($this->once())
            ->method('getAll')
            ->willReturn($envVarConfig);

        $this->assertEquals(
            $expectedConfig,
            $this->mergedConfig->get()
        );
    }

    /**
     * @return array
     */
    public function getDataProvider(): array
    {
        return [
            'empty data' => [
                [],
                [],
                [],
                [],
            ],
            'only default values' => [
                [
                    Deploy::VAR_SCD_STRATEGY => 'simple',
                    Deploy::VAR_SCD_THREADS => 3,
                ],
                [],
                [],
                [
                    Deploy::VAR_SCD_STRATEGY => 'simple',
                    Deploy::VAR_SCD_THREADS => 3,
                ],
            ],
            '.magento.env.yaml global values' => [
                [
                    Deploy::VAR_SCD_STRATEGY => 'simple',
                    Deploy::VAR_SCD_THREADS => 3,
                ],
                [
                    Deploy::STAGE_GLOBAL => [
                        Deploy::VAR_SCD_STRATEGY => 'compact',
                    ],
                    Deploy::STAGE_DEPLOY => [],
                ],
                [],
                [
                    Deploy::VAR_SCD_STRATEGY => 'compact',
                    Deploy::VAR_SCD_THREADS => 3,
                ],
            ],
            '.magento.env.yaml deploys values' => [
                [
                    Deploy::VAR_SCD_STRATEGY => 'simple',
                    Deploy::VAR_SCD_THREADS => 3,
                ],
                [
                    Deploy::STAGE_GLOBAL => [
                        Deploy::VAR_SCD_STRATEGY => 'compact',
                    ],
                    Deploy::STAGE_DEPLOY => [
                        Deploy::VAR_SCD_STRATEGY => 'standard',
                        Deploy::VAR_SCD_THREADS => 5,
                    ],
                ],
                [],
                [
                    Deploy::VAR_SCD_STRATEGY => 'standard',
                    Deploy::VAR_SCD_THREADS => 5,
                ],
            ],
            'environment variables' => [
                [
                    Deploy::VAR_SCD_STRATEGY => 'simple',
                    Deploy::VAR_SCD_THREADS => 3,
                ],
                [
                    Deploy::STAGE_GLOBAL => [
                        Deploy::VAR_SCD_STRATEGY => 'compact',
                    ],
                    Deploy::STAGE_DEPLOY => [
                        Deploy::VAR_SCD_STRATEGY => 'standard',
                        Deploy::VAR_SCD_THREADS => 5,
                    ],
                ],
                [
                    Deploy::VAR_SCD_STRATEGY => 'test',
                    Deploy::VAR_SCD_THREADS => 2,
                ],
                [
                    Deploy::VAR_SCD_STRATEGY => 'test',
                    Deploy::VAR_SCD_THREADS => 2,
                ],
            ],
        ];
    }

    /**
     * @throws ConfigException
     */
    public function testGetWithFileSystemException(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('File system error');
        $this->expectExceptionCode(Error::DEPLOY_CONFIG_UNABLE_TO_READ);

        $this->environmentReaderMock->expects($this->once())
            ->method('read')
            ->willThrowException(new FileSystemException('File system error'));

        $this->mergedConfig->get();
    }

    /**
     * @throws ConfigException
     */
    public function testGetWithParseException(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('File system error');
        $this->expectExceptionCode(Error::DEPLOY_CONFIG_PARSE_FAILED);

        $this->environmentReaderMock->expects($this->once())
            ->method('read')
            ->willThrowException(new ParseException('File system error'));

        $this->mergedConfig->get();
    }

    /**
     * @throws ConfigException
     */
    public function testGetWithSchemaReadException(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('File system error');
        $this->expectExceptionCode(Error::DEPLOY_CONFIG_UNABLE_TO_READ_SCHEMA_YAML);

        $this->schemaMock->expects($this->once())
            ->method('getDefaults')
            ->willThrowException(new FileSystemException('File system error'));

        $this->mergedConfig->get();
    }
}
