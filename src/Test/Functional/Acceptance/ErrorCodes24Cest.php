<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

/**
 * This test cover functionality of state-aware error codes.
 * Checks that failed scenario returns correct error code different to 1 or 255.
 * Checks that var/log/cloud.error.log file was created and contains correct data.
 * Checks that `ece-tools error:show` command returns correct errors info
 *
 * @group php74
 */
class ErrorCodes24Cest extends ErrorCodesCest
{
    /**
     * @var string
     */
    protected $magentoCloudTemplate = '2.4.3';
}
