<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Integration\Schema;

use Magento\MagentoCloud\App\ContainerException;
use Magento\MagentoCloud\Config\Schema;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Config\Schema\Validator;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\Validator\ResultInterface;
use Magento\MagentoCloud\Test\Integration\Container;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ValidatorTest extends TestCase
{
    /**
     * @var Validator
     */
    private $validator;

    /**
     * @throws ContainerException
     */
    protected function setUp(): void
    {
        $container = Container::getInstance(ECE_BP, __DIR__ . '/_files');

        $this->validator = new Validator(
            $container->get(Schema::class),
            $container->get(ResultFactory::class),
            $container->get(Validator\ValidatorFactory::class)
        );
    }

    /**
     * @param string $key
     * @param string|int|bool|array $value
     * @param ResultInterface|null $expected
     * @param string $stage
     * @dataProvider validateDataProvider
     */
    public function testValidate(
        string $key,
        $value,
        ResultInterface $expected = null,
        string $stage = StageConfigInterface::STAGE_DEPLOY
    ): void {
        $expected = $expected ?? new Success();

        $this->assertEquals(
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
            ['keyNotExist', 'someValue', new Error('The keyNotExist variable is not allowed in configuration.')],
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
            [DeployInterface::VAR_CLEAN_STATIC_FILES, true, null],
            [DeployInterface::VAR_CLEAN_STATIC_FILES, false, null],
            [DeployInterface::VAR_SEARCH_CONFIGURATION, ['someOptions' => 'someValue'], null],
            [DeployInterface::VAR_QUEUE_CONFIGURATION, ['someOptions' => 'someValue'], null],
            [DeployInterface::VAR_SESSION_CONFIGURATION, ['someOptions' => 'someValue'], null],
            [DeployInterface::VAR_CRON_CONSUMERS_RUNNER, ['someOptions' => 'someValue'], null],
            [
                StageConfigInterface::VAR_VERBOSE_COMMANDS,
                1,
                new Error(
                    'The VERBOSE_COMMANDS variable contains an invalid value of type integer. ' .
                    'Use the following type: string.'
                ),
            ],
            [
                StageConfigInterface::VAR_VERBOSE_COMMANDS,
                '1',
                new Error(
                    'The VERBOSE_COMMANDS variable contains an invalid value 1. ' .
                    'Use one of the available value options: -v, -vv, -vvv.'
                ),
            ],
            [StageConfigInterface::VAR_SCD_COMPRESSION_LEVEL, 0, null],
            [
                StageConfigInterface::VAR_SCD_COMPRESSION_LEVEL,
                10,
                new Error(
                    'The SCD_COMPRESSION_LEVEL variable contains an invalid value 10. ' .
                    'Use an integer value from 0 to 9.'
                )
            ],
            [
                StageConfigInterface::VAR_SCD_COMPRESSION_LEVEL,
                '1',
                new Error(
                    'The SCD_COMPRESSION_LEVEL variable contains an invalid value of type string. ' .
                    'Use the following type: integer.'
                ),
            ],
            [
                StageConfigInterface::VAR_SCD_STRATEGY,
                12,
                new Error(
                    'The SCD_STRATEGY variable contains an invalid value of type integer. ' .
                    'Use the following type: string.'
                ),
            ],
            [
                StageConfigInterface::VAR_SCD_STRATEGY,
                'quick_error',
                new Error(
                    'The SCD_STRATEGY variable contains an invalid value quick_error. ' .
                    'Use one of the available value options: compact, quick, standard.'
                ),
            ],
            [
                StageConfigInterface::VAR_SCD_STRATEGY,
                'standard_error',
                new Error(
                    'The SCD_STRATEGY variable contains an invalid value standard_error. ' .
                    'Use one of the available value options: compact, quick, standard.'
                )
            ],
            [
                StageConfigInterface::VAR_SCD_THREADS,
                'test',
                new Error(
                    'The SCD_THREADS variable contains an invalid value of type string. ' .
                    'Use the following type: integer.'
                )
            ],
            [
                StageConfigInterface::VAR_SKIP_SCD,
                0,
                new Error(
                    'The SKIP_SCD variable contains an invalid value of type integer. Use the following type: boolean.'
                )
            ],
            [
                StageConfigInterface::VAR_SKIP_SCD,
                'enable',
                new Error(
                    'The SKIP_SCD variable contains an invalid value of type string. Use the following type: boolean.'
                )
            ],
            [
                StageConfigInterface::VAR_SKIP_HTML_MINIFICATION,
                0,
                new Error(
                    'The SKIP_HTML_MINIFICATION variable contains an invalid value of type integer. ' .
                    'Use the following type: boolean.'
                ),
                StageConfigInterface::STAGE_GLOBAL,
            ],
            [
                StageConfigInterface::VAR_SKIP_HTML_MINIFICATION,
                true,
                new Error(
                    'The SKIP_HTML_MINIFICATION variable is not supposed to be in stage deploy. ' .
                    'Move it to one of the possible stages: global.'
                ),
                StageConfigInterface::STAGE_DEPLOY,
            ],
            [
                StageConfigInterface::VAR_SCD_ON_DEMAND,
                0,
                new Error(
                    'The SCD_ON_DEMAND variable contains an invalid value of type integer. ' .
                    'Use the following type: boolean.'
                ),
                StageConfigInterface::STAGE_GLOBAL
            ],
            [
                StageConfigInterface::VAR_DEPLOY_FROM_GIT_OPTIONS,
                'someOption',
                new Error(
                    'The DEPLOY_FROM_GIT_OPTIONS variable contains an invalid value of type string. ' .
                    'Use the following type: array.'
                )
            ],
            [
                DeployInterface::VAR_REDIS_USE_SLAVE_CONNECTION,
                0,
                new Error(
                    'The REDIS_USE_SLAVE_CONNECTION variable contains an invalid value of type integer. ' .
                    'Use the following type: boolean.'
                )
            ],
            [
                DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION,
                0,
                new Error(
                    'The MYSQL_USE_SLAVE_CONNECTION variable contains an invalid value of type integer. ' .
                    'Use the following type: boolean.'
                )
            ],
            [
                DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION,
                true,
                new Error(
                    'The MYSQL_USE_SLAVE_CONNECTION variable is not supposed to be in stage build. ' .
                    'Move it to one of the possible stages: global, deploy.'
                ),
                StageConfigInterface::STAGE_BUILD
            ],
            [
                DeployInterface::VAR_UPDATE_URLS,
                0,
                new Error(
                    'The UPDATE_URLS variable contains an invalid value of type integer. ' .
                    'Use the following type: boolean.'
                )
            ],
            [
                DeployInterface::VAR_CLEAN_STATIC_FILES,
                0,
                new Error(
                    'The CLEAN_STATIC_FILES variable contains an invalid value of type integer. ' .
                    'Use the following type: boolean.'
                )
            ],
            [
                DeployInterface::VAR_SEARCH_CONFIGURATION,
                'someOption',
                new Error(
                    'The SEARCH_CONFIGURATION variable contains an invalid value of type string. ' .
                    'Use the following type: array.'
                )
            ],
            [
                DeployInterface::VAR_CACHE_CONFIGURATION,
                'someOption',
                new Error(
                    'The CACHE_CONFIGURATION variable contains an invalid value of type string. ' .
                    'Use the following type: array.'
                )
            ],
            [
                DeployInterface::VAR_DATABASE_CONFIGURATION,
                'someOption',
                new Error(
                    'The DATABASE_CONFIGURATION variable contains an invalid value of type string. ' .
                    'Use the following type: array.'
                )
            ],
            [
                DeployInterface::VAR_SESSION_CONFIGURATION,
                'someOption',
                new Error(
                    'The SESSION_CONFIGURATION variable contains an invalid value of type string. ' .
                    'Use the following type: array.'
                )
            ],
            [
                DeployInterface::VAR_CRON_CONSUMERS_RUNNER,
                'someOption',
                new Error(
                    'The CRON_CONSUMERS_RUNNER variable contains an invalid value of type string. ' .
                    'Use the following type: array.'
                )
            ],
            [DeployInterface::VAR_SPLIT_DB, [], null],
            [DeployInterface::VAR_SPLIT_DB, [DeployInterface::SPLIT_DB_VALUE_QUOTE], null],
            [
                DeployInterface::VAR_SPLIT_DB,
                [DeployInterface::SPLIT_DB_VALUE_QUOTE, DeployInterface::SPLIT_DB_VALUE_SALES],
                null
            ],
            [
                DeployInterface::VAR_SPLIT_DB,
                DeployInterface::SPLIT_DB_VALUE_QUOTE,
                new Error(
                    'The SPLIT_DB variable contains an invalid value of type string. Use the following type: array.'
                )
            ],
            [
                DeployInterface::VAR_SPLIT_DB,
                ['wrong'],
                new Error(
                    'The SPLIT_DB variable contains the invalid value. It should be an array with following values: '
                    . '[quote, sales].'
                )
            ],
            [
                DeployInterface::VAR_SPLIT_DB,
                ['wrong', DeployInterface::SPLIT_DB_VALUE_QUOTE],
                new Error(
                    'The SPLIT_DB variable contains the invalid value. It should be an array with following values: '
                    . '[quote, sales].'
                )
            ],
        ];
    }
}
