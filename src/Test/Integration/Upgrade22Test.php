<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Integration;

use Magento\MagentoCloud\Command\Build;
use Magento\MagentoCloud\Command\Deploy;
use Magento\MagentoCloud\Command\PostDeploy;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * {@inheritdoc}
 *
 * @group php71
 */
class Upgrade22Test extends AbstractTest
{
    /**
     * @inheritdoc
     */
    public static function setUpBeforeClass()
    {
        Bootstrap::getInstance()->run('2.2.0');
    }

    /**
     * @return array
     */
    public function defaultDataProvider(): array
    {
        return [
            ['2.2.0', '2.2.*'],
        ];
    }
}
