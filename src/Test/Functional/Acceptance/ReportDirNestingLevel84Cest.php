<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

use Magento\MagentoCloud\Config\Stage\BuildInterface;

/**
 * This test runs on the latest version of PHP
 *
 * @group php84
 */
class ReportDirNestingLevel84Cest extends ReportDirNestingLevelCest
{
    /**
     * @var string
     */
    protected string $magentoCloudTemplate = '2.4.8';
}
