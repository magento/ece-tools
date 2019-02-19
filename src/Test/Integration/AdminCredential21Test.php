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
     * @inheritdoc
     */
    protected function setUp()
    {
        Bootstrap::getInstance()->run('2.1.*');
        parent::setUp();

        $this->env = $_ENV;
    }
}
