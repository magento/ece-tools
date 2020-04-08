<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

/**
 *  Test for Split Database Wizard
 */
class SplitDbWizard23Cest extends AbstractSplitDbWizardCest
{
    /**
     * @return array
     */
    protected function dataProviderEnvWithoutSplitDbArchitecture(): array
    {
        return [
            ['version' => '2.3.4'],
        ];
    }

    /**
     * @return array
     */
    protected function dataProviderEnvWithSplitDbArchitecture(): array
    {
        return [
            [
                'types' => [],
                'messages' => [
                    'DB is not split',
                    '- You may split DB using SPLIT_DB variable in .magento.env.yaml file'
                ],
                'version' => '2.3.4',
            ],
            [
                'types' => ['quote'],
                'messages' => ['DB is already split with type(s): quote',],
                'version' => '2.3.4',
            ],
            [
                'types' => ['quote', 'sales'],
                'messages' => ['DB is already split with type(s): quote, sales'],
                'version' => '2.3.4',
            ]
        ];
    }
}
