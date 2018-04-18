<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Validator;

use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Config\Validator\SchemaValidator;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class SchemaValidatorTest extends TestCase
{
    /**
     * @var SchemaValidator
     */
    private $validator;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->validator = new SchemaValidator();
    }

    /**
     * @param string $key
     * @param $value
     * @param $expected
     * @dataProvider validateDataProvider
     */
    public function testValidate(string $key, $value, $expected)
    {
        $this->assertSame(
            $expected,
            $this->validator->validate($key, $value)
        );
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function validateDataProvider(): array
    {
        return [
            ['keyNotExist', 'someValue', null],
            [StageConfigInterface::VAR_VERBOSE_COMMANDS, '-v', null],
            [StageConfigInterface::VAR_VERBOSE_COMMANDS, '-vv', null],
            [StageConfigInterface::VAR_VERBOSE_COMMANDS, '-vvv', null],
            [StageConfigInterface::VAR_VERBOSE_COMMANDS, '', null],
            [StageConfigInterface::VAR_SCD_COMPRESSION_LEVEL, 0, null],
            [StageConfigInterface::VAR_SCD_COMPRESSION_LEVEL, 9, null],
            [StageConfigInterface::VAR_SCD_STRATEGY, 'quick', null],
            [StageConfigInterface::VAR_SCD_STRATEGY, 'compact', null],
            [StageConfigInterface::VAR_SCD_STRATEGY, 'standard', null],
            [StageConfigInterface::VAR_SCD_THREADS, 3, null],
            [StageConfigInterface::VAR_SCD_EXCLUDE_THEMES, 'someTheme', null],
            [StageConfigInterface::VAR_SKIP_SCD, true, null],
            [StageConfigInterface::VAR_SKIP_SCD, false, null],
            [StageConfigInterface::VAR_SKIP_HTML_MINIFICATION, true, null],
            [StageConfigInterface::VAR_SKIP_HTML_MINIFICATION, false, null],
            [StageConfigInterface::VAR_SCD_ON_DEMAND, true, null],
            [StageConfigInterface::VAR_SCD_ON_DEMAND, false, null],
            [StageConfigInterface::VAR_DEPLOY_FROM_GIT_OPTIONS, ['someOptions' => 'someValue'], null],
            [DeployInterface::VAR_REDIS_USE_SLAVE_CONNECTION, true, null],
            [DeployInterface::VAR_REDIS_USE_SLAVE_CONNECTION, false, null],
            [DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION, true, null],
            [DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION, false, null],
            [DeployInterface::VAR_UPDATE_URLS, true, null],
            [DeployInterface::VAR_UPDATE_URLS, false, null],
            [DeployInterface::VAR_STATIC_CONTENT_SYMLINK, true, null],
            [DeployInterface::VAR_STATIC_CONTENT_SYMLINK, false, null],
            [DeployInterface::VAR_CLEAN_STATIC_FILES, true, null],
            [DeployInterface::VAR_CLEAN_STATIC_FILES, false, null],
            [DeployInterface::VAR_SEARCH_CONFIGURATION, ['someOptions' => 'someValue'], null],
            [DeployInterface::VAR_QUEUE_CONFIGURATION, ['someOptions' => 'someValue'], null],
            [DeployInterface::VAR_SESSION_CONFIGURATION, ['someOptions' => 'someValue'], null],
            [DeployInterface::VAR_CRON_CONSUMERS_RUNNER, ['someOptions' => 'someValue'], null],
            [
                StageConfigInterface::VAR_VERBOSE_COMMANDS,
                1,
                'Item VERBOSE_COMMANDS has unexpected type integer. Please use one of next types: string',
            ],
            [
                StageConfigInterface::VAR_VERBOSE_COMMANDS,
                '1',
                'Item VERBOSE_COMMANDS has unexpected value 1. Please use one of next values: -v, -vv, -vvv',
            ],
            [StageConfigInterface::VAR_SCD_COMPRESSION_LEVEL, 0, null],
            [
                StageConfigInterface::VAR_SCD_COMPRESSION_LEVEL,
                10,
                'Item SCD_COMPRESSION_LEVEL has unexpected value 10. Value must be in range 0 - 9.'
            ],
            [
                StageConfigInterface::VAR_SCD_COMPRESSION_LEVEL,
                '1',
                'Item SCD_COMPRESSION_LEVEL has unexpected type string. Please use one of next types: integer',
            ],
            [
                StageConfigInterface::VAR_SCD_STRATEGY,
                12,
                'Item SCD_STRATEGY has unexpected type integer. Please use one of next types: string',
            ],
            [
                StageConfigInterface::VAR_SCD_STRATEGY,
                'quickk',
                'Item SCD_STRATEGY has unexpected value quickk. ' .
                'Please use one of next values: compact, quick, standard',
            ],
            [
                StageConfigInterface::VAR_SCD_STRATEGY,
                'standart',
                'Item SCD_STRATEGY has unexpected value standart. ' .
                'Please use one of next values: compact, quick, standard'
            ],
            [
                StageConfigInterface::VAR_SCD_THREADS,
                'test',
                'Item SCD_THREADS has unexpected type string. Please use one of next types: integer'
            ],
            [
                StageConfigInterface::VAR_SCD_EXCLUDE_THEMES,
                123,
                'Item SCD_EXCLUDE_THEMES has unexpected type integer. Please use one of next types: string'
            ],
            [
                StageConfigInterface::VAR_SKIP_SCD,
                0,
                'Item SKIP_SCD has unexpected type integer. Please use one of next types: boolean'
            ],
            [
                StageConfigInterface::VAR_SKIP_SCD,
                'enable',
                'Item SKIP_SCD has unexpected type string. Please use one of next types: boolean'
            ],
            [
                StageConfigInterface::VAR_SKIP_HTML_MINIFICATION,
                0,
                'Item SKIP_HTML_MINIFICATION has unexpected type integer. Please use one of next types: boolean'
            ],
            [
                StageConfigInterface::VAR_SCD_ON_DEMAND,
                0,
                'Item SCD_ON_DEMAND has unexpected type integer. Please use one of next types: boolean'
            ],
            [
                StageConfigInterface::VAR_DEPLOY_FROM_GIT_OPTIONS,
                'someOption',
                'Item DEPLOY_FROM_GIT_OPTIONS has unexpected type string. Please use one of next types: array'
            ],
            [
                DeployInterface::VAR_REDIS_USE_SLAVE_CONNECTION,
                0,
                'Item REDIS_USE_SLAVE_CONNECTION has unexpected type integer. Please use one of next types: boolean'
            ],
            [
                DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION,
                0,
                'Item MYSQL_USE_SLAVE_CONNECTION has unexpected type integer. Please use one of next types: boolean'
            ],
            [
                DeployInterface::VAR_UPDATE_URLS,
                0,
                'Item UPDATE_URLS has unexpected type integer. Please use one of next types: boolean'
            ],
            [
                DeployInterface::VAR_STATIC_CONTENT_SYMLINK,
                0,
                'Item STATIC_CONTENT_SYMLINK has unexpected type integer. Please use one of next types: boolean'
            ],
            [
                DeployInterface::VAR_CLEAN_STATIC_FILES,
                0,
                'Item CLEAN_STATIC_FILES has unexpected type integer. Please use one of next types: boolean'
            ],
            [
                DeployInterface::VAR_SEARCH_CONFIGURATION,
                'someOption',
                'Item SEARCH_CONFIGURATION has unexpected type string. Please use one of next types: array'
            ],
            [
                DeployInterface::VAR_CACHE_CONFIGURATION,
                'someOption',
                'Item CACHE_CONFIGURATION has unexpected type string. Please use one of next types: array'
            ],
            [
                DeployInterface::VAR_SESSION_CONFIGURATION,
                'someOption',
                'Item SESSION_CONFIGURATION has unexpected type string. Please use one of next types: array'
            ],
            [
                DeployInterface::VAR_CRON_CONSUMERS_RUNNER,
                'someOption',
                'Item CRON_CONSUMERS_RUNNER has unexpected type string. Please use one of next types: array'
            ],
        ];
    }
}
