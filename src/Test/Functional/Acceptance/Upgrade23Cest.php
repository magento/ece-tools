<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

/**
 * @group php72
 */
class Upgrade23Cest extends Upgrade21Cest
{
    /**
     * @return array
     */
    protected function testProvider(): array
    {
        // @TODO change version to 2.3.* after fix in magento core.
        // https://magento2.atlassian.net/browse/MAGECLOUD-3725
        return [
            ['from' => '2.3.0', 'to' => '>=2.3.1 <2.3.2'],
            ['from' => '2.3.3', 'to' => '>=2.3.4 <2.3.5'],
        ];
    }
}
