<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

use Magento\CloudDocker\Test\Functional\Codeception\Docker;

/**
 * This test runs on the latest version of PHP
 *
 * @group php82
 */
class DatabaseConfiguration82Cest extends DatabaseConfigurationCest
{
    /**
     * @var string
     */
    protected $magentoCloudTemplate = '2.4.6';
}
