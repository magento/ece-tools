<?php
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

/**
 * Upgrade test for PHP 8.3
 *
 * @group php83
 */
class Upgrade83Cest extends UpgradeCest
{

    /**
     * @return array
     */
    protected function testProvider(): array
    {
        return [
            ['from' => '2.4.7', 'to' => '>=2.4.7-p1 <2.4.8']
        ];
    }
}
