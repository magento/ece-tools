<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config;

use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\Stage\PostDeployInterface;

/**
 * Configuration schema for .magento.env.yaml file
 */
class Schema
{
    const SCHEMA_TYPE = 'type';
    const SCHEMA_VALUE_VALIDATION = 'value_validation';
    const SCHEMA_STAGE = 'stage';
    const SCHEMA_DEFAULT_VALUE = 'default_values';

    /**
     * @var array
     */
    private $defaults = [];

    /**
     * Returns default values for given stage.
     *
     * @param string $stage
     * @return array
     */
    public function getDefaults(string $stage)
    {
        if (isset($this->defaults[$stage])) {
            return $this->defaults[$stage];
        }

        foreach ($this->getSchema() as $itemName => $itemOptions) {
            if (isset($itemOptions[self::SCHEMA_DEFAULT_VALUE][$stage])) {
                $this->defaults[$stage][$itemName] = $itemOptions[self::SCHEMA_DEFAULT_VALUE][$stage];
            }
        }

        return $this->defaults[$stage];
    }

    /**
     * Returns configuration schema.
     *
     * Each configuration item can have next options:
     * 'type' - possible types (string, integer, array, etc..)
     * 'value_validation' - array of possible values or callback validation function
     * 'stage' - possible stages in which item can be configured
     * 'default_values' - array of default values
     *
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getSchema()
    {
        return [
            StageConfigInterface::VAR_VERBOSE_COMMANDS => [
                self::SCHEMA_TYPE => ['string'],
                self::SCHEMA_VALUE_VALIDATION => ['', '-v', '-vv', '-vvv'],
                self::SCHEMA_STAGE => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_BUILD,
                    StageConfigInterface::STAGE_DEPLOY
                ],
                self::SCHEMA_DEFAULT_VALUE => [
                    StageConfigInterface::STAGE_BUILD => '',
                    StageConfigInterface::STAGE_DEPLOY => '',
                ],
            ],
            StageConfigInterface::VAR_SCD_COMPRESSION_LEVEL => [
                self::SCHEMA_TYPE => ['integer'],
                self::SCHEMA_VALUE_VALIDATION => function (string $key, $value) {
                    if (!in_array($value, range(0, 9))) {
                        return sprintf(
                            'The SCD_COMPRESSION_LEVEL variable contains an invalid value of type string. ' .
                            'Use an integer value from 0 to 9.',
                            $key,
                            $value
                        );
                    }
                },
                self::SCHEMA_STAGE => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_BUILD,
                    StageConfigInterface::STAGE_DEPLOY
                ],
                self::SCHEMA_DEFAULT_VALUE => [
                    StageConfigInterface::STAGE_BUILD => 6,
                    StageConfigInterface::STAGE_DEPLOY => 4,
                ],
            ],
            StageConfigInterface::VAR_SCD_STRATEGY => [
                self::SCHEMA_TYPE => ['string'],
                self::SCHEMA_VALUE_VALIDATION => ['compact', 'quick', 'standard'],
                self::SCHEMA_STAGE => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_BUILD,
                    StageConfigInterface::STAGE_DEPLOY
                ],
                self::SCHEMA_DEFAULT_VALUE => [
                    StageConfigInterface::STAGE_BUILD => '',
                    StageConfigInterface::STAGE_DEPLOY => '',
                ],
            ],
            StageConfigInterface::VAR_SCD_THREADS => [
                self::SCHEMA_TYPE => ['integer'],
                self::SCHEMA_STAGE => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_BUILD,
                    StageConfigInterface::STAGE_DEPLOY
                ],
                self::SCHEMA_DEFAULT_VALUE => [
                    StageConfigInterface::STAGE_BUILD => 1,
                    StageConfigInterface::STAGE_DEPLOY => 1,
                ],
            ],
            StageConfigInterface::VAR_SCD_EXCLUDE_THEMES => [
                self::SCHEMA_TYPE => ['string'],
                self::SCHEMA_STAGE => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_BUILD,
                    StageConfigInterface::STAGE_DEPLOY
                ],
                self::SCHEMA_DEFAULT_VALUE => [
                    StageConfigInterface::STAGE_BUILD => '',
                    StageConfigInterface::STAGE_DEPLOY => '',
                ],
            ],
            StageConfigInterface::VAR_SCD_MATRIX => [
                self::SCHEMA_TYPE => ['array'],
                self::SCHEMA_STAGE => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_BUILD,
                    StageConfigInterface::STAGE_DEPLOY
                ],
                self::SCHEMA_DEFAULT_VALUE => [
                    StageConfigInterface::STAGE_BUILD => [],
                    StageConfigInterface::STAGE_DEPLOY => [],
                ],
            ],
            StageConfigInterface::VAR_SKIP_SCD => [
                self::SCHEMA_TYPE => ['boolean'],
                self::SCHEMA_STAGE => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_BUILD,
                    StageConfigInterface::STAGE_DEPLOY
                ],
                self::SCHEMA_DEFAULT_VALUE => [
                    StageConfigInterface::STAGE_BUILD => false,
                    StageConfigInterface::STAGE_DEPLOY => false,
                ],
            ],
            StageConfigInterface::VAR_SKIP_HTML_MINIFICATION => [
                self::SCHEMA_TYPE => ['boolean'],
                self::SCHEMA_STAGE => [
                    StageConfigInterface::STAGE_GLOBAL
                ],
                self::SCHEMA_DEFAULT_VALUE => [
                    StageConfigInterface::STAGE_GLOBAL => true,
                ],
            ],
            StageConfigInterface::VAR_SCD_ON_DEMAND => [
                self::SCHEMA_TYPE => ['boolean'],
                self::SCHEMA_STAGE => [
                    StageConfigInterface::STAGE_GLOBAL
                ],
                self::SCHEMA_DEFAULT_VALUE => [
                    StageConfigInterface::STAGE_GLOBAL => false,
                ],
            ],
            StageConfigInterface::VAR_DEPLOYED_MAGENTO_VERSION_FROM_GIT => [
                self::SCHEMA_TYPE => ['string'],
                self::SCHEMA_STAGE => [
                    StageConfigInterface::STAGE_GLOBAL
                ],
                self::SCHEMA_DEFAULT_VALUE => [
                    StageConfigInterface::STAGE_GLOBAL => '',
                ],
            ],
            StageConfigInterface::VAR_DEPLOY_FROM_GIT_OPTIONS => [
                self::SCHEMA_TYPE => ['array'],
                self::SCHEMA_STAGE => [
                    StageConfigInterface::STAGE_GLOBAL
                ],
                self::SCHEMA_DEFAULT_VALUE => [
                    StageConfigInterface::STAGE_GLOBAL => [],
                ],
            ],
            StageConfigInterface::VAR_MIN_LOGGING_LEVEL => [
                self::SCHEMA_TYPE => ['string'],
                self::SCHEMA_VALUE_VALIDATION => [
                    '',
                    Log::LEVEL_DEBUG,
                    Log::LEVEL_INFO,
                    Log::LEVEL_NOTICE,
                    Log::LEVEL_WARNING,
                    Log::LEVEL_ERROR,
                    Log::LEVEL_CRITICAL,
                    Log::LEVEL_ALERT,
                    Log::LEVEL_EMERGENCY,
                ],
                self::SCHEMA_STAGE => [
                    StageConfigInterface::STAGE_GLOBAL,
                ],
                self::SCHEMA_DEFAULT_VALUE => [
                    StageConfigInterface::STAGE_GLOBAL => '',
                ],
            ],
            DeployInterface::VAR_REDIS_USE_SLAVE_CONNECTION => [
                self::SCHEMA_TYPE => ['boolean'],
                self::SCHEMA_STAGE => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_DEPLOY
                ],
                self::SCHEMA_DEFAULT_VALUE => [
                    StageConfigInterface::STAGE_DEPLOY => false,
                ],
            ],
            DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION => [
                self::SCHEMA_TYPE => ['boolean'],
                self::SCHEMA_STAGE => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_DEPLOY
                ],
                self::SCHEMA_DEFAULT_VALUE => [
                    StageConfigInterface::STAGE_DEPLOY => false,
                ],
            ],
            DeployInterface::VAR_UPDATE_URLS => [
                self::SCHEMA_TYPE => ['boolean'],
                self::SCHEMA_STAGE => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_DEPLOY
                ],
                self::SCHEMA_DEFAULT_VALUE => [
                    StageConfigInterface::STAGE_DEPLOY => true,
                ],
            ],
            DeployInterface::VAR_STATIC_CONTENT_SYMLINK => [
                self::SCHEMA_TYPE => ['boolean'],
                self::SCHEMA_STAGE => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_DEPLOY
                ],
                self::SCHEMA_DEFAULT_VALUE => [
                    StageConfigInterface::STAGE_DEPLOY => true,
                ],
            ],
            DeployInterface::VAR_CLEAN_STATIC_FILES => [
                self::SCHEMA_TYPE => ['boolean'],
                self::SCHEMA_STAGE => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_DEPLOY
                ],
                self::SCHEMA_DEFAULT_VALUE => [
                    StageConfigInterface::STAGE_DEPLOY => true,
                ],
            ],
            DeployInterface::VAR_SEARCH_CONFIGURATION => [
                self::SCHEMA_TYPE => ['array'],
                self::SCHEMA_STAGE => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_DEPLOY
                ],
                self::SCHEMA_DEFAULT_VALUE => [
                    StageConfigInterface::STAGE_DEPLOY => [],
                ],
            ],
            DeployInterface::VAR_QUEUE_CONFIGURATION => [
                self::SCHEMA_TYPE => ['array'],
                self::SCHEMA_STAGE => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_DEPLOY
                ],
                self::SCHEMA_DEFAULT_VALUE => [
                    StageConfigInterface::STAGE_DEPLOY => [],
                ],
            ],
            DeployInterface::VAR_CACHE_CONFIGURATION => [
                self::SCHEMA_TYPE => ['array'],
                self::SCHEMA_STAGE => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_DEPLOY
                ],
                self::SCHEMA_DEFAULT_VALUE => [
                    StageConfigInterface::STAGE_DEPLOY => [],
                ],
            ],
            DeployInterface::VAR_SESSION_CONFIGURATION => [
                self::SCHEMA_TYPE => ['array'],
                self::SCHEMA_STAGE => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_DEPLOY
                ],
                self::SCHEMA_DEFAULT_VALUE => [
                    StageConfigInterface::STAGE_DEPLOY => [],
                ],
            ],
            DeployInterface::VAR_DATABASE_CONFIGURATION => [
                self::SCHEMA_TYPE => ['array'],
                self::SCHEMA_STAGE => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_DEPLOY
                ],
                self::SCHEMA_DEFAULT_VALUE => [
                    StageConfigInterface::STAGE_DEPLOY => [],
                ],
            ],
            DeployInterface::VAR_CRON_CONSUMERS_RUNNER => [
                self::SCHEMA_TYPE => ['array'],
                self::SCHEMA_STAGE => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_DEPLOY
                ],
                self::SCHEMA_DEFAULT_VALUE => [
                    StageConfigInterface::STAGE_DEPLOY => [],
                ],
            ],
            DeployInterface::VAR_ENABLE_GOOGLE_ANALYTICS => [
                self::SCHEMA_TYPE => ['boolean'],
                self::SCHEMA_STAGE => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_DEPLOY
                ],
                self::SCHEMA_DEFAULT_VALUE => [
                    StageConfigInterface::STAGE_DEPLOY => false,
                ],
            ],
            DeployInterface::VAR_GENERATED_CODE_SYMLINK => [
                self::SCHEMA_TYPE => ['boolean'],
                self::SCHEMA_STAGE => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_DEPLOY
                ],
                self::SCHEMA_DEFAULT_VALUE => [
                    StageConfigInterface::STAGE_DEPLOY => true,
                ],
            ],
            PostDeployInterface::VAR_WARM_UP_PAGES => [
                self::SCHEMA_TYPE => ['array'],
                self::SCHEMA_STAGE => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_POST_DEPLOY
                ],
                self::SCHEMA_DEFAULT_VALUE => [
                    StageConfigInterface::STAGE_POST_DEPLOY => [''],
                ],
            ],
        ];
    }
}
