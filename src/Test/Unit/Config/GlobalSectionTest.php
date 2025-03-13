<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Config\GlobalSection;
use Magento\MagentoCloud\Config\Schema;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Config\Environment\Reader as EnvironmentReader;
use Magento\MagentoCloud\Config\StageConfigInterface;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * @inheritdoc
 */
class GlobalSectionTest extends TestCase
{
    /**
     * @var GlobalSection
     */
    private $config;

    /**
     * @var EnvironmentReader|MockObject
     */
    private $environmentReaderMock;

    /**
     * @var Schema|MockObject
     */
    private $schemaMock;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        $this->environmentReaderMock = $this->createMock(EnvironmentReader::class);
        $this->schemaMock = $this->createMock(Schema::class);
        $this->schemaMock->method('getDefaults')
            ->with(StageConfigInterface::STAGE_GLOBAL)
            ->willReturn([
                StageConfigInterface::VAR_SCD_ON_DEMAND => false,
                StageConfigInterface::VAR_SKIP_HTML_MINIFICATION => false,
                StageConfigInterface::VAR_DEPLOYED_MAGENTO_VERSION_FROM_GIT => false,
                StageConfigInterface::VAR_DEPLOY_FROM_GIT_OPTIONS => [],
            ]);

        $this->config = new GlobalSection($this->environmentReaderMock, $this->schemaMock);
    }

    /**
     * @param string $name
     * @param array $config
     * @param bool $expectedValue
     * @throws ConfigException
     *
     * @dataProvider getDataProvider
     */
    public function testGet(string $name, array $config, $expectedValue)
    {
        $this->environmentReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([GlobalSection::SECTION_STAGE => $config]);

        $this->assertSame($expectedValue, $this->config->get($name));
    }

    /**
     * @return array
     */
    public function getDataProvider(): array
    {
        return [
            [
                'name' => GlobalSection::VAR_SCD_ON_DEMAND,
                'config' => [
                    StageConfigInterface::STAGE_GLOBAL => [
                        GlobalSection::VAR_SCD_ON_DEMAND => true,
                    ],
                    StageConfigInterface::STAGE_BUILD => [
                        GlobalSection::VAR_SCD_ON_DEMAND => false
                    ],
                    StageConfigInterface::STAGE_DEPLOY => [
                        GlobalSection::VAR_SCD_ON_DEMAND => false
                    ],
                ],
                'expectedValue' => true,
            ],
            [
                'name' => GlobalSection::VAR_SCD_ON_DEMAND,
                'config' => [
                    StageConfigInterface::STAGE_GLOBAL => [
                        GlobalSection::VAR_SCD_ON_DEMAND => false,
                    ],
                    StageConfigInterface::STAGE_BUILD => [
                        GlobalSection::VAR_SCD_ON_DEMAND => true
                    ],
                    StageConfigInterface::STAGE_DEPLOY => [
                        GlobalSection::VAR_SCD_ON_DEMAND => true
                    ],
                ],
                'expectedValue' => false,
            ],
            [
                'name' => GlobalSection::VAR_SCD_ON_DEMAND,
                'config' => [
                    StageConfigInterface::STAGE_BUILD => [
                        GlobalSection::VAR_SCD_ON_DEMAND => true
                    ],
                    StageConfigInterface::STAGE_DEPLOY => [
                        GlobalSection::VAR_SCD_ON_DEMAND => true
                    ],
                ],
                'expectedValue' => false,
            ],
            [
                'name' => GlobalSection::VAR_SKIP_HTML_MINIFICATION,
                'config' => [
                    StageConfigInterface::STAGE_GLOBAL => [
                        GlobalSection::VAR_SKIP_HTML_MINIFICATION => true,
                    ],
                    StageConfigInterface::STAGE_BUILD => [
                        GlobalSection::VAR_SKIP_HTML_MINIFICATION => false
                    ],
                    StageConfigInterface::STAGE_DEPLOY => [
                        GlobalSection::VAR_SKIP_HTML_MINIFICATION => false
                    ],
                ],
                'expectedValue' => true,
            ],
            [
                'name' => GlobalSection::VAR_SKIP_HTML_MINIFICATION,
                'config' => [
                    StageConfigInterface::STAGE_GLOBAL => [
                        GlobalSection::VAR_SKIP_HTML_MINIFICATION => false,
                    ],
                    StageConfigInterface::STAGE_BUILD => [
                        GlobalSection::VAR_SKIP_HTML_MINIFICATION => true
                    ],
                    StageConfigInterface::STAGE_DEPLOY => [
                        GlobalSection::VAR_SKIP_HTML_MINIFICATION => true
                    ],
                ],
                'expectedValue' => false,
            ],
            [
                'name' => GlobalSection::VAR_SKIP_HTML_MINIFICATION,
                'config' => [
                    StageConfigInterface::STAGE_BUILD => [
                        GlobalSection::VAR_SKIP_HTML_MINIFICATION => true
                    ],
                    StageConfigInterface::STAGE_DEPLOY => [
                        GlobalSection::VAR_SKIP_HTML_MINIFICATION => true
                    ],
                ],
                'expectedValue' => false,
            ],
        ];
    }

    /**
     * @throws ConfigException
     */
    public function testNotExists(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Config NOT_EXISTS_VALUE was not defined.');
        $this->expectExceptionCode(Error::GLOBAL_CONFIG_NOT_DEFINED);

        $this->environmentReaderMock->expects($this->never())
            ->method('read');

        $this->config->get('NOT_EXISTS_VALUE');
    }

    /**
     * @throws ConfigException
     */
    public function testUnableToReadMagentoEnvYAml(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Some error');
        $this->expectExceptionCode(Error::GLOBAL_CONFIG_UNABLE_TO_READ);

        $this->environmentReaderMock->expects($this->once())
            ->method('read')
            ->willThrowException(new FileSystemException('Some error'));

        $this->config->get(GlobalSection::VAR_SCD_ON_DEMAND);
    }

    /**
     * @throws ConfigException
     */
    public function testUnableToParseMagentoEnvYaml(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Some error');
        $this->expectExceptionCode(Error::GLOBAL_CONFIG_PARSE_FAILED);

        $this->environmentReaderMock->expects($this->once())
            ->method('read')
            ->willThrowException(new ParseException('Some error'));

        $this->config->get(GlobalSection::VAR_SCD_ON_DEMAND);
    }

    /**
     * @throws ConfigException
     */
    public function testUnableToReadSchemaFile(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Some error');
        $this->expectExceptionCode(Error::GLOBAL_CONFIG_UNABLE_TO_READ_SCHEMA_YAML);

        $this->schemaMock->expects($this->once())
            ->method('getDefaults')
            ->willThrowException(new FileSystemException('Some error'));

        $this->config->get(GlobalSection::VAR_SCD_ON_DEMAND);
    }
}
