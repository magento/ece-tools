<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

/**
 * @group php70
 */
class Upgrade21Cest extends UpgradeCest
{
    /**
     * @return array
     */
    protected function testProvider()
    {
        return [
            ['from' => '2.1.12', 'to' => '2.2.0']
        ];
    }
}
