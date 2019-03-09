<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\DockerIntegration;

/**
 * @inheritdoc
 *
 * @group php70
 */
class Upgrade21Test extends UpgradeTest
{
    /**
     * @return array
     */
    public function defaultDataProvider(): array
    {
        return [
            ['2.1.12', '2.2.0']
        ];
    }
}
