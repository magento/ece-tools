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

/**
 *  Checks split database wizard functionality
 *
 * @group php73
 */
class SplitDbWizard73Cest extends SplitDbWizardCest
{
    /**
     * @var boolean
     */
    protected $removeEs = true;

    /**
     * @return array
     */
    protected function dataProviderMagentoCloudVersions(): array
    {
        return [
            ['version' => '2.3.4'],
            ['version' => '2.3.5'],
        ];
    }
}
