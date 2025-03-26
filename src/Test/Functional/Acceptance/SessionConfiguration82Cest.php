<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

/**
 * This scenario checks that session can be configured through environment variable SESSION_CONFIGURATION
 * Zephyr ID MAGECLOUD-46
 *
 * @group php82
 */
class SessionConfiguration82Cest extends SessionConfigurationCest
{
    /**
     * @var string
     */
    protected $magentoCloudTemplate = '2.4.6';
}
