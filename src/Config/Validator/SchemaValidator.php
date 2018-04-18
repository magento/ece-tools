<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator;

use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\Stage\PostDeployInterface;
use Magento\MagentoCloud\Config\StageConfigInterface;

/**
 * Validates configuration types and values by schema.
 */
class SchemaValidator
{
    const SCHEMA_TYPE = 'type';
    const SCHEMA_VALUE = 'value';
    const SCHEMA_STAGE = 'stage';

    /**
     * @param string $key
     * @param string $stage
     * @param mixed $value
     * @return null|string
     */
    public function validate(string $key, string $stage, $value)
    {
        $schema = $this->getSchema();
        if (!isset($schema[$key])) {
            return null;
        }

        $type = gettype($value);
        $allowedTypes = $schema[$key][self::SCHEMA_TYPE] ?? [];
        $allowedValues = $schema[$key][self::SCHEMA_VALUE] ?? [];
        $allowedStages = $schema[$key][self::SCHEMA_STAGE] ?? [];

        if ($allowedTypes && !in_array($type, $allowedTypes)) {
            return sprintf(
                'Item %s has unexpected type %s. Please use one of next types: %s',
                $key,
                $type,
                implode(', ', $allowedTypes)
            );
        }

        if (!in_array($stage, $allowedStages)) {
            return sprintf(
                'Item %s is not supposed to be in stage %s. Please move it to one of possible stages: %s',
                $key,
                $stage,
                implode(', ', $allowedStages)
            );
        }

        if (is_callable($allowedValues)) {
            return $allowedValues($key, $value);
        }

        if ($allowedValues && !in_array($value, $allowedValues)) {
            return sprintf(
                'Item %s has unexpected value %s. Please use one of next values: %s',
                $key,
                $value,
                implode(', ', array_filter($allowedValues))
            );
        }
    }

    /**
     * Returns validation schema for stage section options.
     *
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function getSchema(): array
    {
        return [
            StageConfigInterface::VAR_VERBOSE_COMMANDS => [
                self::SCHEMA_TYPE => ['string'],
                self::SCHEMA_VALUE => ['', '-v', '-vv', '-vvv'],
                self::SCHEMA_STAGE => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_BUILD,
                    StageConfigInterface::STAGE_DEPLOY
                ]
            ],
            StageConfigInterface::VAR_SCD_COMPRESSION_LEVEL => [
                self::SCHEMA_TYPE => ['integer'],
                self::SCHEMA_VALUE => function (string $key, $value) {
                    if (!in_array($value, range(0, 9))) {
                        return sprintf(
                            'Item %s has unexpected value %s. Value must be in range 0 - 9.',
                            $key,
                            $value
                        );
                    }
                },
                self::SCHEMA_STAGE => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_BUILD,
                    StageConfigInterface::STAGE_DEPLOY
                ]
            ],
            StageConfigInterface::VAR_SCD_STRATEGY => [
                self::SCHEMA_TYPE => ['string'],
                self::SCHEMA_VALUE => ['compact', 'quick', 'standard'],
                self::SCHEMA_STAGE => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_BUILD,
                    StageConfigInterface::STAGE_DEPLOY
                ]
            ],
            StageConfigInterface::VAR_SCD_THREADS => [
                self::SCHEMA_TYPE => ['integer'],
                self::SCHEMA_STAGE => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_BUILD,
                    StageConfigInterface::STAGE_DEPLOY
                ]
            ],
            StageConfigInterface::VAR_SCD_EXCLUDE_THEMES => [
                self::SCHEMA_TYPE => ['string'],
                self::SCHEMA_STAGE => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_BUILD,
                    StageConfigInterface::STAGE_DEPLOY
                ]
            ],
            StageConfigInterface::VAR_SKIP_SCD => [
                self::SCHEMA_TYPE => ['boolean'],
                self::SCHEMA_STAGE => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_BUILD,
                    StageConfigInterface::STAGE_DEPLOY
                ]
            ],
            StageConfigInterface::VAR_SKIP_HTML_MINIFICATION => [
                self::SCHEMA_TYPE => ['boolean'],
                self::SCHEMA_STAGE => [
                    StageConfigInterface::STAGE_GLOBAL
                ]
            ],
            StageConfigInterface::VAR_SCD_ON_DEMAND => [
                self::SCHEMA_TYPE => ['boolean'],
                self::SCHEMA_STAGE => [
                    StageConfigInterface::STAGE_GLOBAL
                ]
            ],
            StageConfigInterface::VAR_DEPLOYED_MAGENTO_VERSION_FROM_GIT => [
                self::SCHEMA_TYPE => ['array', 'boolean'],
                self::SCHEMA_STAGE => [
                    StageConfigInterface::STAGE_GLOBAL
                ]
            ],
            StageConfigInterface::VAR_DEPLOY_FROM_GIT_OPTIONS => [
                self::SCHEMA_TYPE => ['array'],
                self::SCHEMA_STAGE => [
                    StageConfigInterface::STAGE_GLOBAL
                ]
            ],
            DeployInterface::VAR_REDIS_USE_SLAVE_CONNECTION => [
                self::SCHEMA_TYPE => ['boolean'],
                self::SCHEMA_STAGE => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_DEPLOY
                ]
            ],
            DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION => [
                self::SCHEMA_TYPE => ['boolean'],
                self::SCHEMA_STAGE => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_DEPLOY
                ]
            ],
            DeployInterface::VAR_UPDATE_URLS => [
                self::SCHEMA_TYPE => ['boolean'],
                self::SCHEMA_STAGE => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_DEPLOY
                ]
            ],
            DeployInterface::VAR_STATIC_CONTENT_SYMLINK => [
                self::SCHEMA_TYPE => ['boolean'],
                self::SCHEMA_STAGE => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_DEPLOY
                ]
            ],
            DeployInterface::VAR_CLEAN_STATIC_FILES => [
                self::SCHEMA_TYPE => ['boolean'],
                self::SCHEMA_STAGE => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_DEPLOY
                ]
            ],
            DeployInterface::VAR_SEARCH_CONFIGURATION => [
                self::SCHEMA_TYPE => ['array'],
                self::SCHEMA_STAGE => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_DEPLOY
                ]
            ],
            DeployInterface::VAR_QUEUE_CONFIGURATION => [
                self::SCHEMA_TYPE => ['array'],
                self::SCHEMA_STAGE => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_DEPLOY
                ]
            ],
            DeployInterface::VAR_CACHE_CONFIGURATION => [
                self::SCHEMA_TYPE => ['array'],
                self::SCHEMA_STAGE => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_DEPLOY
                ]
            ],
            DeployInterface::VAR_SESSION_CONFIGURATION => [
                self::SCHEMA_TYPE => ['array'],
                self::SCHEMA_STAGE => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_DEPLOY
                ]
            ],
            DeployInterface::VAR_CRON_CONSUMERS_RUNNER => [
                self::SCHEMA_TYPE => ['array'],
                self::SCHEMA_STAGE => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_DEPLOY
                ]
            ],
            PostDeployInterface::VAR_WARM_UP_PAGES => [
                self::SCHEMA_TYPE => ['array'],
                self::SCHEMA_STAGE => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_POST_DEPLOY
                ]
            ]
        ];
    }
}
