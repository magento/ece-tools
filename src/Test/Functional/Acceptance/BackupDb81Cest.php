<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

use CliTester;
use Codeception\Example;
use Exception;
use Robo\Exception\TaskException;

/**
 * Checks database backup functionality
 * @group php81
 */
class BackupDb244Cest extends BackupDbCest
{
    /**
     * @return array
     */
    protected function dataProviderMagentoCloudVersions(): array
    {
        return [
            ['version' => '2.4.4'],
        ];
    }
}
