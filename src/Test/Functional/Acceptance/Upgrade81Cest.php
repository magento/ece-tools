<?php
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

/**
 * Upgrade test for PHP 8.1
 *
 * @group php81
 */
class Upgrade81Cest extends UpgradeCest
{
    /**
     * @return array
     */
    protected function testProvider(): array
    {
        return [
            ['from' => '2.4.4', 'to' => '>=2.4.5 <2.4.6']
            
        ];
    }
}
