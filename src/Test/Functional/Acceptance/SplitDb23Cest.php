<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

/**
 * Checks Split Database Functionality
 */
class SplitDbCest extends AbstractSplitDbCest
{
    /**
     * @return array
     */
    protected function dataProviderVersion(): array
    {
        return [
            ['version' => '2.3.4']
        ];
    }

    /**
     * @return array
     */
    protected function dataProviderDeploySplitDbWithInvalidSplitTypes(): array
    {
        return [
            [
                'types' => 'quote',
                'messages' => [
                    'ERROR: Fix configuration with given suggestions:',
                    '- Environment configuration is not valid.',
                    'Correct the following items in your .magento.env.yaml file:',
                    'The SPLIT_DB variable contains an invalid value of type string. Use the following type: array.',
                ],
                'version' => '2.3.4',
            ],
            [
                'types' => ['checkout'],
                'messages' => [
                    'ERROR: Fix configuration with given suggestions:',
                    '- Environment configuration is not valid.',
                    'Correct the following items in your .magento.env.yaml file:',
                    'The SPLIT_DB variable contains the invalid value.',
                    'It should be array with next available values: [quote, sales].'
                ],
                'version' => '2.3.4',
            ],
            [
                'types' => ['quote', 'checkout'],
                'messages' => [
                    'ERROR: Fix configuration with given suggestions:',
                    '- Environment configuration is not valid.'
                    , 'Correct the following items in your .magento.env.yaml file:',
                    'The SPLIT_DB variable contains the invalid value.'
                    , 'It should be array with next available values: [quote, sales].',
                ],
                'version' => '2.3.4'
            ],
        ];
    }

    /**
     * @return array
     */
    protected function dataProviderTestDeploySplitDb(): array
    {
        return [
            [
                'connection' => ['checkout'],
                'types' => ['quote'],
                'messages' => [
                    'INFO: Quote tables were split to DB magento2 in db-quote',
                    'INFO: Running setup upgrade.',
                ],
                'version' => '2.3.4',
            ],
            [
                'connection' => ['sales'],
                'types' => ['sales'],
                'messages' => [
                    'INFO: Sales tables were split to DB magento2 in db-sales',
                    'INFO: Running setup upgrade.',
                ],
                'version' => '2.3.4',
            ],
            [
                'connection' => ['checkout', 'sales'],
                'types' => ['quote', 'sales'],
                'messages' => [
                    'INFO: Quote tables were split to DB magento2 in db-quote',
                    'INFO: Running setup upgrade.',
                    'INFO: Sales tables were split to DB magento2 in db-sales',
                    'INFO: Running setup upgrade.',
                ],
                'version' => '2.3.4',
            ],
        ];
    }
}
