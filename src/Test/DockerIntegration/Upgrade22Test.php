<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\DockerIntegration;

/**
 * @inheritdoc
 *
 * @group php71
 */
class Upgrade22Test extends UpgradeTest
{
    /**
     * @return array
     */
    public function testDataProvider(): array
    {
        return [
            ['2.2.0', '2.2.*']
        ];
    }
}
