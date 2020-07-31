<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

/**
 * @group php71
 */
class AdminCredential21Cest extends AdminCredentialCest
{
    /**
     * @var boolean
     */
    protected $removeEs = true;

    /**
     * @var string
     */
    protected $magentoCloudTemplate = '2.1.17';
}
