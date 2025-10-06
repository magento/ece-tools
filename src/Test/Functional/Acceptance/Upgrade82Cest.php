<?php
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

/**
 * Upgrade test for PHP 8.2
 *
 * @group php82
 */
class Upgrade82Cest extends UpgradeCest
{
    /**
     * @return array
     */
    protected function testProvider(): array
    {
        return [
            ['from' => '2.4.6', 'to' => '>=2.4.7 <2.4.8'],
        ];
    }
}
