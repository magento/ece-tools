<?php
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

/**
 * Upgrade test for PHP 8.4
 *
 * @group php84
 */
class Upgrade84Cest extends UpgradeCest
{
    /**
     * Provides upgrade test cases for PHP 8.4-supported Magento versions.
     *
     * @return array
     */
    protected function testProvider(): array
    {
        return [
            ['from' => '2.4.8', 'to' => '>=2.4.8-p1 <2.4.9-alpha3']
        ];
    }
}
