<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Validator;

use Magento\MagentoCloud\Config\Schema;
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
        $this->validator = new SchemaValidator(new Schema());
    }

    /**
     * @param string $key
     * @param $value
     * @param $expected
     * @param string $stage
     * @dataProvider validateDataProvider
     */
    public function testValidate(string $key, $value, $expected, string $stage = StageConfigInterface::STAGE_DEPLOY)
    {
        $this->assertSame(
            $expected,
            $this->validator->validate($key, $stage, $value)
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
            ['keyNotExist', 'someValue', 'The keyNotExist variable is not allowed in configuration.'],
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
            [StageConfigInterface::VAR_SKIP_HTML_MINIFICATION, true, null, StageConfigInterface::STAGE_GLOBAL],
            [StageConfigInterface::VAR_SKIP_HTML_MINIFICATION, false, null, StageConfigInterface::STAGE_GLOBAL],
            [StageConfigInterface::VAR_SCD_ON_DEMAND, true, null, StageConfigInterface::STAGE_GLOBAL],
            [StageConfigInterface::VAR_SCD_ON_DEMAND, false, null, StageConfigInterface::STAGE_GLOBAL],
            [
                StageConfigInterface::VAR_DEPLOY_FROM_GIT_OPTIONS,
                ['someOptions' => 'someValue'],
                null,
                StageConfigInterface::STAGE_GLOBAL
            ],
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
                'The VERBOSE_COMMANDS variable contains an invalid value of type integer. ' .
                'Use the following types: string.',
            ],
            [
                StageConfigInterface::VAR_VERBOSE_COMMANDS,
                '1',
                'The VERBOSE_COMMANDS variable contains an invalid value 1. ' .
                'Use one of the available value options: -v, -vv, -vvv.',
            ],
            [StageConfigInterface::VAR_SCD_COMPRESSION_LEVEL, 0, null],
            [
                StageConfigInterface::VAR_SCD_COMPRESSION_LEVEL,
                10,
                'The SCD_COMPRESSION_LEVEL variable contains an invalid value 10. ' .
                'Use an integer value from 0 to 9.'
            ],
            [
                StageConfigInterface::VAR_SCD_COMPRESSION_LEVEL,
                '1',
                'The SCD_COMPRESSION_LEVEL variable contains an invalid value of type string. '.
                'Use the following types: integer.',
            ],
            [
                StageConfigInterface::VAR_SCD_STRATEGY,
                12,
                'The SCD_STRATEGY variable contains an invalid value of type integer. ' .
                'Use the following types: string.',
            ],
            [
                StageConfigInterface::VAR_SCD_STRATEGY,
                'quickk',
                'The SCD_STRATEGY variable contains an invalid value quickk. ' .
                'Use one of the available value options: compact, quick, standard.',
            ],
            [
                StageConfigInterface::VAR_SCD_STRATEGY,
                'standart',
                'The SCD_STRATEGY variable contains an invalid value standart. ' .
                'Use one of the available value options: compact, quick, standard.'
            ],
            [
                StageConfigInterface::VAR_SCD_THREADS,
                'test',
                'The SCD_THREADS variable contains an invalid value of type string. Use the following types: integer.'
            ],
            [
                StageConfigInterface::VAR_SCD_EXCLUDE_THEMES,
                123,
                'The SCD_EXCLUDE_THEMES variable contains an invalid value of type integer. ' .
                'Use the following types: string.'
            ],
            [
                StageConfigInterface::VAR_SKIP_SCD,
                0,
                'The SKIP_SCD variable contains an invalid value of type integer. Use the following types: boolean.'
            ],
            [
                StageConfigInterface::VAR_SKIP_SCD,
                'enable',
                'The SKIP_SCD variable contains an invalid value of type string. Use the following types: boolean.'
            ],
            [
                StageConfigInterface::VAR_SKIP_HTML_MINIFICATION,
                0,
                'The SKIP_HTML_MINIFICATION variable contains an invalid value of type integer. ' .
                'Use the following types: boolean.',
                StageConfigInterface::STAGE_GLOBAL,
            ],
            [
                StageConfigInterface::VAR_SKIP_HTML_MINIFICATION,
                true,
                'The SKIP_HTML_MINIFICATION variable is not supposed to be in stage deploy. ' .
                'Move it to one of the possible stages: global.',
                StageConfigInterface::STAGE_DEPLOY,
            ],
            [
                StageConfigInterface::VAR_SCD_ON_DEMAND,
                0,
                'The SCD_ON_DEMAND variable contains an invalid value of type integer. ' .
                'Use the following types: boolean.',
                StageConfigInterface::STAGE_GLOBAL
            ],
            [
                StageConfigInterface::VAR_DEPLOY_FROM_GIT_OPTIONS,
                'someOption',
                'The DEPLOY_FROM_GIT_OPTIONS variable contains an invalid value of type string. ' .
                'Use the following types: array.'
            ],
            [
                DeployInterface::VAR_REDIS_USE_SLAVE_CONNECTION,
                0,
                'The REDIS_USE_SLAVE_CONNECTION variable contains an invalid value of type integer. ' .
                'Use the following types: boolean.'
            ],
            [
                DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION,
                0,
                'The MYSQL_USE_SLAVE_CONNECTION variable contains an invalid value of type integer. ' .
                'Use the following types: boolean.'
            ],
            [
                DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION,
                true,
                'The MYSQL_USE_SLAVE_CONNECTION variable is not supposed to be in stage build. ' .
                'Move it to one of the possible stages: global, deploy.',
                StageConfigInterface::STAGE_BUILD
            ],
            [
                DeployInterface::VAR_UPDATE_URLS,
                0,
                'The UPDATE_URLS variable contains an invalid value of type integer. ' .
                'Use the following types: boolean.'
            ],
            [
                DeployInterface::VAR_STATIC_CONTENT_SYMLINK,
                0,
                'The STATIC_CONTENT_SYMLINK variable contains an invalid value of type integer. ' .
                'Use the following types: boolean.'
            ],
            [
                DeployInterface::VAR_CLEAN_STATIC_FILES,
                0,
                'The CLEAN_STATIC_FILES variable contains an invalid value of type integer. ' .
                'Use the following types: boolean.'
            ],
            [
                DeployInterface::VAR_SEARCH_CONFIGURATION,
                'someOption',
                'The SEARCH_CONFIGURATION variable contains an invalid value of type string. ' .
                'Use the following types: array.'
            ],
            [
                DeployInterface::VAR_CACHE_CONFIGURATION,
                'someOption',
                'The CACHE_CONFIGURATION variable contains an invalid value of type string. ' .
                'Use the following types: array.'
            ],
            [
                DeployInterface::VAR_DATABASE_CONFIGURATION,
                'someOption',
                'The DATABASE_CONFIGURATION variable contains an invalid value of type string. ' .
                'Use the following types: array.'
            ],
            [
                DeployInterface::VAR_SESSION_CONFIGURATION,
                'someOption',
                'The SESSION_CONFIGURATION variable contains an invalid value of type string. ' .
                'Use the following types: array.'
            ],
            [
                DeployInterface::VAR_CRON_CONSUMERS_RUNNER,
                'someOption',
                'The CRON_CONSUMERS_RUNNER variable contains an invalid value of type string. ' .
                'Use the following types: array.'
            ],
        ];
    }
}
