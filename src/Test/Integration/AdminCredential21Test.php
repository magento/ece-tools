<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Integration;

/**
 * {@inheritdoc}
 *
 * @group php70
 */
class AdminCredential21Test extends AdminCredentialTest
{
    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public static function setUpBeforeClass()
    {
        Bootstrap::getInstance()->run('2.1.*');
    }
}
