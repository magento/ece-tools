<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config;

use Magento\MagentoCloud\Config\Stage\BuildInterface;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\Stage\PostDeployInterface;
use Magento\MagentoCloud\Filesystem\SystemList;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml;

/**
 * Configuration schema for .magento.env.yaml file
 */
class Schema
{
    public const SCHEMA_TYPE = 'type';
    public const SCHEMA_ALLOWED_VALUES = 'allowed';
    public const SCHEMA_VALUE_VALIDATORS = 'validators';
    public const SCHEMA_STAGES = 'stages';
    public const SCHEMA_SYSTEM = 'system';
    public const SCHEMA_DEFAULT_VALUE = 'default';
    public const SCHEMA_DESCRIPTION = 'description';
    public const SCHEMA_MAGENTO_VERSION = 'magento_version';
    public const SCHEMA_EXAMPLES = 'examples';

    public const SCHEMA_EXAMPLE_COMMENT = 'comment';

    /**
     * @var SystemList
     */
    private $systemList;

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var array
     */
    private $defaults = [];

    /**
     * @param SystemList $systemList
     * @param Parser $parser
     */
    public function __construct(SystemList $systemList, Parser $parser)
    {
        $this->systemList = $systemList;
        $this->parser = $parser;
    }

    /**
     * Returns default values for given stage.
     *
     * @param string $stage
     * @return array
     */
    public function getDefaults(string $stage): array
    {
        if (isset($this->defaults[$stage])) {
            return $this->defaults[$stage];
        }

        foreach ($this->getSchema() as $itemName => $itemOptions) {
            if (array_key_exists($stage, $itemOptions[self::SCHEMA_DEFAULT_VALUE])) {
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
    public function getSchema(): array
    {
        $schema = $this->parser->parseFile(
            $this->systemList->getConfig() . '/schema.yaml',
            Yaml::PARSE_CONSTANT
        );

        return array_replace($schema, [
            StageConfigInterface::VAR_DEPLOYED_MAGENTO_VERSION_FROM_GIT => [
                self::SCHEMA_TYPE => 'string',
                self::SCHEMA_STAGES => [
                    StageConfigInterface::STAGE_GLOBAL
                ],
                self::SCHEMA_DEFAULT_VALUE => [
                    StageConfigInterface::STAGE_GLOBAL => '',
                ],
            ],
            StageConfigInterface::VAR_DEPLOY_FROM_GIT_OPTIONS => [
                self::SCHEMA_TYPE => 'array',
                self::SCHEMA_STAGES => [
                    StageConfigInterface::STAGE_GLOBAL
                ],
                self::SCHEMA_DEFAULT_VALUE => [
                    StageConfigInterface::STAGE_GLOBAL => [],
                ],
            ],
            BuildInterface::VAR_ERROR_REPORT_DIR_NESTING_LEVEL => [
                self::SCHEMA_TYPE => 'integer',
                self::SCHEMA_ALLOWED_VALUES => range(0, 32),
                self::SCHEMA_STAGES => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_BUILD,
                ],
                self::SCHEMA_DEFAULT_VALUE => [StageConfigInterface::STAGE_BUILD => 1]
            ],
            DeployInterface::VAR_LOCK_PROVIDER => [
                self::SCHEMA_TYPE => ['string'],
                self::SCHEMA_STAGE => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_DEPLOY
                ],
                self::SCHEMA_VALUE_VALIDATION => ['db', 'file'],
                self::SCHEMA_DEFAULT_VALUE => [
                    StageConfigInterface::STAGE_DEPLOY => 'file',
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
            DeployInterface::VAR_SPLIT_DB => [
                self::SCHEMA_TYPE => ['array'],
                self::SCHEMA_STAGE => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_DEPLOY,
                ],
                self::SCHEMA_DEFAULT_VALUE => [
                    StageConfigInterface::STAGE_DEPLOY => [],
                ],
                self::SCHEMA_VALUE_VALIDATION => function (string $key, $value) {
                    if (array_diff($value, DeployInterface::SPLIT_DB_VALUES)) {
                        return sprintf(
                            'The %s variable contains the invalid value. '
                            . 'It should be array with next available values: [%s].',
                            $key,
                            implode(', ', DeployInterface::SPLIT_DB_VALUES)
                        );
                    }
                },
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
            DeployInterface::VAR_FORCE_UPDATE_URLS => [
                self::SCHEMA_TYPE => ['boolean'],
                self::SCHEMA_STAGE => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_DEPLOY
                ],
                self::SCHEMA_DEFAULT_VALUE => [
                    StageConfigInterface::STAGE_DEPLOY => false,
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
                self::SCHEMA_TYPE => 'array',
                self::SCHEMA_STAGES => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_DEPLOY
                ],
                self::SCHEMA_DEFAULT_VALUE => [
                    StageConfigInterface::STAGE_DEPLOY => [],
                ],
            ],
            DeployInterface::VAR_ELASTICSUITE_CONFIGURATION => [
                self::SCHEMA_TYPE => 'array',
                self::SCHEMA_STAGES => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_DEPLOY
                ],
                self::SCHEMA_DEFAULT_VALUE => [
                    StageConfigInterface::STAGE_DEPLOY => [],
                ],
            ],
            DeployInterface::VAR_QUEUE_CONFIGURATION => [
                self::SCHEMA_TYPE => 'array',
                self::SCHEMA_STAGES => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_DEPLOY
                ],
                self::SCHEMA_DEFAULT_VALUE => [
                    StageConfigInterface::STAGE_DEPLOY => [],
                ],
            ],
            DeployInterface::VAR_CACHE_CONFIGURATION => [
                self::SCHEMA_TYPE => 'array',
                self::SCHEMA_STAGES => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_DEPLOY
                ],
                self::SCHEMA_DEFAULT_VALUE => [
                    StageConfigInterface::STAGE_DEPLOY => [],
                ],
            ],
            DeployInterface::VAR_SESSION_CONFIGURATION => [
                self::SCHEMA_TYPE => 'array',
                self::SCHEMA_STAGES => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_DEPLOY
                ],
                self::SCHEMA_DEFAULT_VALUE => [
                    StageConfigInterface::STAGE_DEPLOY => [],
                ],
            ],
            DeployInterface::VAR_DATABASE_CONFIGURATION => [
                self::SCHEMA_TYPE => 'array',
                self::SCHEMA_STAGES => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_DEPLOY
                ],
                self::SCHEMA_DEFAULT_VALUE => [
                    StageConfigInterface::STAGE_DEPLOY => [],
                ],
            ],
            DeployInterface::VAR_RESOURCE_CONFIGURATION => [
                self::SCHEMA_TYPE => 'array',
                self::SCHEMA_STAGES => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_DEPLOY
                ],
                self::SCHEMA_DEFAULT_VALUE => [
                    StageConfigInterface::STAGE_DEPLOY => [],
                ],
            ],
            DeployInterface::VAR_CRON_CONSUMERS_RUNNER => [
                self::SCHEMA_TYPE => 'array',
                self::SCHEMA_STAGES => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_DEPLOY
                ],
                self::SCHEMA_DEFAULT_VALUE => [
                    StageConfigInterface::STAGE_DEPLOY => [],
                ],
            ],
            DeployInterface::VAR_CONSUMERS_WAIT_FOR_MAX_MESSAGES => [
                self::SCHEMA_TYPE => 'boolean',
                self::SCHEMA_STAGES => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_DEPLOY
                ],
                self::SCHEMA_DEFAULT_VALUE => [
                    StageConfigInterface::STAGE_DEPLOY => false,
                ],
            ],
            DeployInterface::VAR_ENABLE_GOOGLE_ANALYTICS => [
                self::SCHEMA_TYPE => 'boolean',
                self::SCHEMA_STAGES => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_DEPLOY
                ],
                self::SCHEMA_DEFAULT_VALUE => [
                    StageConfigInterface::STAGE_DEPLOY => false,
                ],
            ],
            DeployInterface::VAR_GENERATED_CODE_SYMLINK => [
                self::SCHEMA_TYPE => 'boolean',
                self::SCHEMA_STAGES => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_DEPLOY
                ],
                self::SCHEMA_DEFAULT_VALUE => [
                    StageConfigInterface::STAGE_DEPLOY => true,
                ],
            ],
            PostDeployInterface::VAR_WARM_UP_PAGES => [
                self::SCHEMA_TYPE => 'array',
                self::SCHEMA_STAGES => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_POST_DEPLOY
                ],
                self::SCHEMA_DEFAULT_VALUE => [
                    StageConfigInterface::STAGE_POST_DEPLOY => [''],
                ],
            ],
            PostDeployInterface::VAR_TTFB_TESTED_PAGES => [
                self::SCHEMA_TYPE => 'array',
                self::SCHEMA_STAGES => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_POST_DEPLOY
                ],
                self::SCHEMA_DEFAULT_VALUE => [
                    StageConfigInterface::STAGE_POST_DEPLOY => [],
                ],
            ],
            StageConfigInterface::VAR_X_FRAME_CONFIGURATION => [
                self::SCHEMA_TYPE => 'string',
                self::SCHEMA_STAGES => [
                    StageConfigInterface::STAGE_GLOBAL
                ],
                self::SCHEMA_DEFAULT_VALUE => [
                    StageConfigInterface::STAGE_GLOBAL => 'SAMEORIGIN'
                ]
            ]
        ]);
    }
}
