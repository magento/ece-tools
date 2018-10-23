<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config;

use Magento\MagentoCloud\Config\Schema;
use Magento\MagentoCloud\Config\Stage\BuildInterface;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\Stage\PostDeployInterface;
use Magento\MagentoCloud\Config\StageConfigInterface;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class SchemaTest extends TestCase
{
    /**
     * @var Schema
     */
    private $schema;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->schema = new Schema();
    }

    public function testGetDefaultsForBuild()
    {
        $this->assertEquals(
            [
                BuildInterface::VAR_SCD_STRATEGY => '',
                BuildInterface::VAR_SKIP_SCD => false,
                BuildInterface::VAR_SCD_COMPRESSION_LEVEL => 6,
                BuildInterface::VAR_SCD_THREADS => 1,
                BuildInterface::VAR_SCD_EXCLUDE_THEMES => '',
                BuildInterface::VAR_VERBOSE_COMMANDS => '',
                BuildInterface::VAR_SCD_MATRIX => [],
            ],
            $this->schema->getDefaults(StageConfigInterface::STAGE_BUILD)
        );
    }

    public function testGetDefaultsForDeploy()
    {
        $this->assertEquals(
            [
                DeployInterface::VAR_SCD_STRATEGY => '',
                DeployInterface::VAR_SCD_COMPRESSION_LEVEL => 4,
                DeployInterface::VAR_SEARCH_CONFIGURATION => [],
                DeployInterface::VAR_QUEUE_CONFIGURATION => [],
                DeployInterface::VAR_CACHE_CONFIGURATION => [],
                DeployInterface::VAR_SESSION_CONFIGURATION => [],
                DeployInterface::VAR_DATABASE_CONFIGURATION => [],
                DeployInterface::VAR_VERBOSE_COMMANDS => '',
                DeployInterface::VAR_CRON_CONSUMERS_RUNNER => [],
                DeployInterface::VAR_CLEAN_STATIC_FILES => true,
                DeployInterface::VAR_STATIC_CONTENT_SYMLINK => true,
                DeployInterface::VAR_UPDATE_URLS => true,
                DeployInterface::VAR_SKIP_SCD => false,
                DeployInterface::VAR_SCD_THREADS => 1,
                DeployInterface::VAR_GENERATED_CODE_SYMLINK => true,
                DeployInterface::VAR_SCD_EXCLUDE_THEMES => '',
                DeployInterface::VAR_REDIS_USE_SLAVE_CONNECTION => false,
                DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION => false,
                DeployInterface::VAR_ENABLE_GOOGLE_ANALYTICS => false,
                DeployInterface::VAR_SCD_MATRIX => [],
            ],
            $this->schema->getDefaults(StageConfigInterface::STAGE_DEPLOY)
        );
    }

    public function testGetDefaultsForPostDeploy()
    {
        $this->assertEquals(
            [
                PostDeployInterface::VAR_WARM_UP_PAGES => [
                    '',
                ]
            ],
            $this->schema->getDefaults(StageConfigInterface::STAGE_POST_DEPLOY)
        );
    }

    public function testGetDefaultsForGlobalSection()
    {
        $this->assertEquals(
            [
                StageConfigInterface::VAR_SCD_ON_DEMAND => false,
                StageConfigInterface::VAR_SKIP_HTML_MINIFICATION => true,
                StageConfigInterface::VAR_DEPLOYED_MAGENTO_VERSION_FROM_GIT => false,
                StageConfigInterface::VAR_DEPLOY_FROM_GIT_OPTIONS => [],
                StageConfigInterface::VAR_MIN_LOGGING_LEVEL => '',
            ],
            $this->schema->getDefaults(StageConfigInterface::STAGE_GLOBAL)
        );
    }

    public function testGetSchemaItemsExists()
    {
        $requiredItems = [
            StageConfigInterface::VAR_SCD_COMPRESSION_LEVEL,
            StageConfigInterface::VAR_SCD_STRATEGY,
            StageConfigInterface::VAR_SCD_THREADS,
            StageConfigInterface::VAR_SCD_EXCLUDE_THEMES,
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
            DeployInterface::VAR_STATIC_CONTENT_SYMLINK,
            DeployInterface::VAR_UPDATE_URLS,
            DeployInterface::VAR_REDIS_USE_SLAVE_CONNECTION,
            DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION,
            DeployInterface::VAR_GENERATED_CODE_SYMLINK,
            PostDeployInterface::VAR_WARM_UP_PAGES,
        ];

        foreach ($requiredItems as $item) {
            $this->assertArrayHasKey($item, $this->schema->getSchema());
        }
    }
}
