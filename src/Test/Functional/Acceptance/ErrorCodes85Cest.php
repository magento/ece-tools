<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

use Magento\MagentoCloud\App\Error;

/**
 * This test cover functionality of state-aware error codes.
 * Checks that failed scenario returns correct error code different to 1 or 255.
 * Checks that var/log/cloud.error.log file was created and contains correct data.
 * Checks that `ece-tools error:show` command returns correct errors info
 *
 * @group php85
 */
class ErrorCodes85Cest extends ErrorCodesCest
{
    /**
     * Data provider for Magento Cloud versions.
     *
     * @var string
     */
    protected string $magentoCloudTemplate = '2.4.9-alpha-opensearch3.0';
}
