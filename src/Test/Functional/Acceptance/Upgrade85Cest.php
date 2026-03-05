<?php
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

/**
 * Upgrade test for PHP 8.5
 *
 * @group php85
 */
class Upgrade85Cest extends UpgradeCest
{
    /**
     * Provides upgrade test cases for PHP 8.5-supported Magento versions.
     *
     * @return array
     */
    protected function testProvider(): array
    {
        return [
            ['from' => '2.4.9-alpha-opensearch3.0', 'to' => '>=2.4.9-alpha-opensearch3.0 <2.4.10']
        ];
    }
}
