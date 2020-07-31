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
class Upgrade21Cest extends UpgradeCest
{
    /**
     * @var boolean
     */
    protected $removeEs = true;

    /**
     * @return array
     */
    protected function testProvider(): array
    {
        return [
            ['from' => '2.1.16', 'to' => '>=2.2.0 <2.2.1']
        ];
    }
}
