<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

use CliTester;

/**
 * This test runs on the latest version of PHP
 *
 * @group php83
 */
class ErrorMessage83Cest extends ErrorMessageCest
{
    /**
     * @var string
     */
    protected string $magentoCloudTemplate = '2.4.7';
}
