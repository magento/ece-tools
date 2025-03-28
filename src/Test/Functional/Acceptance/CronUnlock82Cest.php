<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

/**
 * Test for cron:unlock.
 *
 * @group php82
 */
class CronUnlock82Cest extends CronUnlockCest
{
    /**
     * @var string
     */
    protected $magentoCloudTemplate = '2.4.6';
}
