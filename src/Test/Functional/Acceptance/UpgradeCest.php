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
 * @group php73
 */
class UpgradeCest extends Upgrade23Cest
{
    /**
     * @var boolean
     */
    protected $removeEs = false;

    /**
     * @return array
     */
    protected function testProvider(): array
    {
        return [
            ['from' => '2.3.5', 'to' => '>=2.4.0 <2.4.1'],
        ];
    }
}
