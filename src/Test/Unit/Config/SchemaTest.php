<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config;

use Magento\MagentoCloud\Config\Schema;
use Magento\MagentoCloud\Config\Stage\BuildInterface;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\Stage\PostDeployInterface;
use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Config\SystemConfigInterface;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Filesystem\SystemList;
use Magento\MagentoCloud\Util\YamlNormalizer;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Parser;

/**
 * @inheritdoc
 */
#[AllowMockObjectsWithoutExpectations]
class SchemaTest extends TestCase
{
    /**
     * @var Schema|MockObject
     */
    private $schema;

    /**
     * @var SystemList|MockObject
     */
    private $systemListMock;

    /**
     * @var Parser|MockObject
     */
    private $parserMock;

    /**
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @var YamlNormalizer|MockObject
     */
    private YamlNormalizer $yamlNormalizerMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->systemListMock     = $this->createMock(SystemList::class);
        $this->parserMock         = $this->createMock(Parser::class);
        $this->fileMock           = $this->createMock(File::class);
        $this->yamlNormalizerMock = $this->createMock(YamlNormalizer::class);

        $this->systemListMock->method('getConfig')
            ->willReturn(__DIR__ . '/../../../../config');

        $this->fileMock->method('fileGetContents')
            ->willReturn(file_get_contents(ECE_BP . '/config/schema.yaml'));

        $this->parserMock->method('parse')
            ->willReturnCallback(function ($content) {
                $parser = new Parser();
                return $parser->parse($content);
            });

        $this->yamlNormalizerMock->method('normalize')
            ->willReturnArgument(0);

        $this->schema = new Schema(
            $this->systemListMock,
            $this->parserMock,
            $this->fileMock,
            $this->yamlNormalizerMock
        );
    }

    /**
     * Test for getDefaults method for build stage.
     *
     * @return void
     * @throws FileSystemException
     */
    public function testGetDefaultsForBuild(): void
    {
        $this->assertEquals(
            [
                BuildInterface::VAR_SCD_STRATEGY => '',
                BuildInterface::VAR_SKIP_SCD => false,
                BuildInterface::VAR_SCD_COMPRESSION_LEVEL => 6,
                BuildInterface::VAR_SCD_COMPRESSION_TIMEOUT => 600,
                BuildInterface::VAR_SCD_THREADS => -1,
                BuildInterface::VAR_VERBOSE_COMMANDS => '',
                BuildInterface::VAR_SCD_MATRIX => [],
                BuildInterface::VAR_SCD_MAX_EXEC_TIME => null,
                BuildInterface::VAR_ERROR_REPORT_DIR_NESTING_LEVEL => 1,
                BuildInterface::VAR_SCD_USE_BALER => false,
                BuildInterface::VAR_QUALITY_PATCHES => [],
                BuildInterface::VAR_SKIP_COMPOSER_DUMP_AUTOLOAD => false,
                BuildInterface::VAR_SCD_NO_PARENT => false,
            ],
            $this->schema->getDefaults(StageConfigInterface::STAGE_BUILD)
        );
    }

    /**
     * Test for getDefaults method for deploy stage.
     *
     * @return void
     * @throws FileSystemException
     */
    public function testGetDefaultsForDeploy(): void
    {
        $this->assertEquals(
            [
                DeployInterface::VAR_SCD_STRATEGY => '',
                DeployInterface::VAR_SCD_COMPRESSION_LEVEL => 4,
                DeployInterface::VAR_SCD_MAX_EXEC_TIME => null,
                DeployInterface::VAR_SCD_COMPRESSION_TIMEOUT => 600,
                DeployInterface::VAR_SEARCH_CONFIGURATION => [],
                DeployInterface::VAR_ELASTICSUITE_CONFIGURATION => [],
                DeployInterface::VAR_QUEUE_CONFIGURATION => [],
                DeployInterface::VAR_CACHE_CONFIGURATION => [],
                DeployInterface::VAR_SESSION_CONFIGURATION => [],
                DeployInterface::VAR_DATABASE_CONFIGURATION => [],
                DeployInterface::VAR_VERBOSE_COMMANDS => '',
                DeployInterface::VAR_CRON_CONSUMERS_RUNNER => [],
                DeployInterface::VAR_CLEAN_STATIC_FILES => true,
                DeployInterface::VAR_UPDATE_URLS => true,
                DeployInterface::VAR_FORCE_UPDATE_URLS => false,
                DeployInterface::VAR_SKIP_SCD => false,
                DeployInterface::VAR_SCD_THREADS => -1,
                DeployInterface::VAR_GENERATED_CODE_SYMLINK => true,
                DeployInterface::VAR_REDIS_USE_SLAVE_CONNECTION => false,
                DeployInterface::VAR_VALKEY_USE_SLAVE_CONNECTION => false,
                DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION => false,
                DeployInterface::VAR_ENABLE_GOOGLE_ANALYTICS => false,
                DeployInterface::VAR_SCD_MATRIX => [],
                DeployInterface::VAR_RESOURCE_CONFIGURATION => [],
                DeployInterface::VAR_LOCK_PROVIDER => 'file',
                DeployInterface::VAR_CONSUMERS_WAIT_FOR_MAX_MESSAGES => false,
                DeployInterface::VAR_SPLIT_DB => [],
                DeployInterface::VAR_CACHE_REDIS_BACKEND => 'Cm_Cache_Backend_Redis',
                DeployInterface::VAR_CACHE_VALKEY_BACKEND => 'Cm_Cache_Backend_Redis',
                DeployInterface::VAR_REMOTE_STORAGE => [],
                DeployInterface::VAR_SCD_NO_PARENT => false,
                DeployInterface::VAR_USE_LUA => false,
                DeployInterface::VAR_LUA_KEY => true,
            ],
            $this->schema->getDefaults(StageConfigInterface::STAGE_DEPLOY)
        );
    }

    /**
     * Test for getDefaults method for post-deploy stage.
     *
     * @return void
     * @throws FileSystemException
     */
    public function testGetDefaultsForPostDeploy(): void
    {
        $this->assertEquals(
            [
                PostDeployInterface::VAR_WARM_UP_PAGES => [
                    '',
                ],
                PostDeployInterface::VAR_WARM_UP_CONCURRENCY => 0,
                PostDeployInterface::VAR_TTFB_TESTED_PAGES => [],
                PostDeployInterface::VAR_VERBOSE_COMMANDS => '',
            ],
            $this->schema->getDefaults(StageConfigInterface::STAGE_POST_DEPLOY)
        );
        /** Lazy loading */
        $this->assertEquals(
            [
                PostDeployInterface::VAR_WARM_UP_PAGES => [
                    '',
                ],
                PostDeployInterface::VAR_WARM_UP_CONCURRENCY => 0,
                PostDeployInterface::VAR_TTFB_TESTED_PAGES => [],
                PostDeployInterface::VAR_VERBOSE_COMMANDS => '',
            ],
            $this->schema->getDefaults(StageConfigInterface::STAGE_POST_DEPLOY)
        );
    }

    /**
     * Test get defaults for system variables method.
     *
     * @return void
     * @throws FileSystemException
     */
    public function testGetDefaultsForSystemVariables(): void
    {
        $this->assertEquals(
            [
                SystemConfigInterface::VAR_ENV_RELATIONSHIPS => 'MAGENTO_CLOUD_RELATIONSHIPS',
                SystemConfigInterface::VAR_ENV_ROUTES => 'MAGENTO_CLOUD_ROUTES',
                SystemConfigInterface::VAR_ENV_VARIABLES => 'MAGENTO_CLOUD_VARIABLES',
                SystemConfigInterface::VAR_ENV_APPLICATION => 'MAGENTO_CLOUD_APPLICATION',
                SystemConfigInterface::VAR_ENV_ENVIRONMENT => 'MAGENTO_CLOUD_ENVIRONMENT',
            ],
            $this->schema->getDefaults(SystemConfigInterface::SYSTEM_VARIABLES)
        );
    }

    /**
     * Test get defaults for global section method.
     *
     * @return void
     * @throws FileSystemException
     */
    public function testGetDefaultsForGlobalSection(): void
    {
        $this->assertEquals(
            [
                StageConfigInterface::VAR_SCD_ON_DEMAND => false,
                StageConfigInterface::VAR_SKIP_HTML_MINIFICATION => true,
                StageConfigInterface::VAR_DEPLOYED_MAGENTO_VERSION_FROM_GIT => '',
                StageConfigInterface::VAR_DEPLOY_FROM_GIT_OPTIONS => [],
                StageConfigInterface::VAR_MIN_LOGGING_LEVEL => '',
                StageConfigInterface::VAR_X_FRAME_CONFIGURATION => 'SAMEORIGIN',
                StageConfigInterface::VAR_ENABLE_EVENTING => false,
                StageConfigInterface::VAR_ENABLE_WEBHOOKS => false,
            ],
            $this->schema->getDefaults(StageConfigInterface::STAGE_GLOBAL)
        );
    }

    /**
     * Test get schema items exists method.
     *
     * @return void
     * @throws FileSystemException
     */
    public function testGetSchemaItemsExists(): void
    {
        $requiredItems = [
            StageConfigInterface::VAR_SCD_COMPRESSION_LEVEL,
            StageConfigInterface::VAR_SCD_STRATEGY,
            StageConfigInterface::VAR_SCD_THREADS,
            StageConfigInterface::VAR_SKIP_SCD,
            StageConfigInterface::VAR_VERBOSE_COMMANDS,
            StageConfigInterface::VAR_SCD_ON_DEMAND,
            StageConfigInterface::VAR_SKIP_HTML_MINIFICATION,
            StageConfigInterface::VAR_SCD_MATRIX,
            StageConfigInterface::VAR_DEPLOYED_MAGENTO_VERSION_FROM_GIT,
            StageConfigInterface::VAR_DEPLOY_FROM_GIT_OPTIONS,
            DeployInterface::VAR_QUEUE_CONFIGURATION,
            DeployInterface::VAR_SEARCH_CONFIGURATION,
            DeployInterface::VAR_CACHE_CONFIGURATION,
            DeployInterface::VAR_SESSION_CONFIGURATION,
            DeployInterface::VAR_DATABASE_CONFIGURATION,
            DeployInterface::VAR_CRON_CONSUMERS_RUNNER,
            DeployInterface::VAR_CLEAN_STATIC_FILES,
            DeployInterface::VAR_UPDATE_URLS,
            DeployInterface::VAR_REDIS_USE_SLAVE_CONNECTION,
            DeployInterface::VAR_VALKEY_USE_SLAVE_CONNECTION,
            DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION,
            DeployInterface::VAR_GENERATED_CODE_SYMLINK,
            DeployInterface::VAR_SPLIT_DB,
            PostDeployInterface::VAR_WARM_UP_PAGES,
            PostDeployInterface::VAR_TTFB_TESTED_PAGES,
            SystemConfigInterface::VAR_ENV_RELATIONSHIPS,
            SystemConfigInterface::VAR_ENV_ROUTES,
            SystemConfigInterface::VAR_ENV_VARIABLES,
            SystemConfigInterface::VAR_ENV_APPLICATION,
            SystemConfigInterface::VAR_ENV_ENVIRONMENT
        ];

        foreach ($requiredItems as $item) {
            $this->assertArrayHasKey($item, $this->schema->getVariables());
        }
    }
}
