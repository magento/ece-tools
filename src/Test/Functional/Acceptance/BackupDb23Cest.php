<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

/**
 * Checks backup databases
 */
class BackupDb23Cest extends AbstractBackupDbCest
{
    /**
     * @return array
     */
    protected function dataProviderBackUpDbUnavailable(): array
    {
        return [
            [
                'databases' => ['quote'],
                'message' => 'CRITICAL: Environment does not have connection'
                    . ' `checkout` associated with database `quote`',
                'version' => '2.3.4',
            ],
            [
                'databases' => ['sales'],
                'message' => 'CRITICAL: Environment does not have connection'
                    . ' `sales` associated with database `sales`',
                'version' => '2.3.4',
            ],
            [
                'databases' => ['quote', 'sales'],
                'message' => 'CRITICAL: Environment does not have connection'
                    . ' `checkout` associated with database `quote`',
                'version' => '2.3.4',
            ]
        ];
    }

    /**
     * @return array
     */
    protected function dataProviderBackUpDbIncorrect(): array
    {
        return [
            ['version' => '2.3.4']
        ];
    }

    /**
     * @return array
     */
    protected function dataProviderCreateBackUp(): array
    {
        return [
            [
                'splitDbTypes' => [],
                'databases' => [],
                'dbDumps' => ['main'],
                'version' => '2.3.4',
            ],
            [
                'splitDbTypes' => ['quote', 'sales'],
                'databases' => [],
                'dbDumps' => ['main', 'quote', 'sales'],
                'version' => '2.3.4',
            ],
            [
                'splitDbTypes' => ['quote', 'sales'],
                'databases' => ['main', 'sales'],
                'dbDumps' => ['main', 'sales'],
                'version' => '2.3.4',
            ]
        ];
    }
}
