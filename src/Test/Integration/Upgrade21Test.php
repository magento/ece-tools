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
class Upgrade21Test extends UpgradeTest
{
    /**
     * @inheritdoc
     */
    public static function setUpBeforeClass()
    {
        Bootstrap::getInstance()->run('2.1.12');
    }

    /**
     * @return array
     */
    public function defaultDataProvider(): array
    {
        return [
            ['2.1.12', '2.2.0'],
        ];
    }
}
