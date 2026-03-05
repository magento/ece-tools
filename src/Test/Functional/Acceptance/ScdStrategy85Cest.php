<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

/**
 * This test runs on the latest version of PHP
 *
 * @group php85
 */
class ScdStrategy85Cest extends ScdStrategyCest
{
    /**
     * Data provider for Magento Cloud versions.
     *
     * @var string
     */
    protected string $magentoCloudTemplate = '2.4.9-alpha-opensearch3.0';
}
